'use strict';
// PreToolUse hook (matcher: Bash): deny destructive commands.
//
// Why node, not jq: jq is not installed on this machine, but node v24 is.
// node does real JSON parsing + regex matching, which is more robust than
// jq+grep for this job.
//
// Contract:
//   - Reads the hook JSON payload on stdin.
//   - On a match it prints a PreToolUse "deny" decision (stdout, exit 0).
//   - On anything it doesn't handle (no match, non-Bash payload, malformed
//     stdin, parse error) it prints NOTHING and exits 0 => fail OPEN, so the
//     hook can never break Claude's normal permission flow.
const fs = require('fs');

let cmd = '';
try {
  const raw = fs.readFileSync(0, 'utf8'); // fd 0 = stdin
  const payload = raw ? JSON.parse(raw) : {};
  if (payload && payload.tool_input && typeof payload.tool_input.command === 'string') {
    cmd = payload.tool_input.command;
  }
} catch (_) {
  process.exit(0); // malformed stdin / not a Bash call => fail open
}

if (!cmd) process.exit(0);

const reason = check(cmd);
if (reason) {
  process.stdout.write(JSON.stringify({
    hookSpecificOutput: {
      hookEventName: 'PreToolUse',
      permissionDecision: 'deny',
      permissionDecisionReason: reason,
    },
  }));
}
// No output => allow; normal permission resolution continues.

// Returns a denial reason string, or null to allow.
function check(cmd) {
  // 1) Database-wiping migrations.
  if (/\bmigrate:fresh\b/.test(cmd)) {
    return 'Blocked: artisan migrate:fresh drops ALL tables (full DB wipe). Use plain `migrate` or add a new migration instead.';
  }
  if (/\bmigrate:refresh\b/.test(cmd)) {
    return 'Blocked: artisan migrate:refresh rolls back and re-runs every migration (data loss).';
  }

  // 2) Dangerous git push.
  if (/\bgit\s+push\b/.test(cmd)) {
    // Protect main/master as the push target. Matches a bare `main`/`master`
    // token (source ref) OR a refspec dest like `HEAD:main`. A branch NAMED
    // `feature/main-refactor` is NOT matched (`main` there follows `/`).
    if (/(^|[\s:])(main|master)(\s|$|:)/.test(cmd)) {
      return 'Blocked: git push targets main/master. Push to a feature branch instead.';
    }
    // Force push — but ALLOW --force-with-lease. Strip the lease form first,
    // then look for a bare --force token or a short -f flag cluster.
    const stripped = cmd.replace(/--force-with-lease/g, '');
    const forceLong = /(^|\s)--force(\s|$)/.test(stripped);
    const forceShort = /(^|\s)-[a-zA-Z]*f[a-zA-Z]*/.test(stripped); // -f, -fu, -fP, ...
    if (forceLong || forceShort) {
      return 'Blocked: force push (--force / -f). Use --force-with-lease if a force-push is truly necessary.';
    }
  }

  // 3) Catastrophic recursive rm of a SYSTEM ROOT.
  //    `rm -rf node_modules`, `rm -rf ./storage/logs/*`, `rm -rf /tmp/x` etc. are allowed;
  //    only recursive deletes of /, ~, $HOME, /*, a drive mount (/c/), or a drive root (C:\) are blocked.
  if (/\brm\b/.test(cmd)) {
    const hasRecursive = /(^|\s)--recursive\b/.test(cmd) || /(^|\s)-[a-zA-Z]*[rR]/.test(cmd);
    if (hasRecursive) {
      const m = cmd.match(/\brm\b\s+(.*)/);
      const args = m ? m[1].split(/\s+/) : [];
      // Token is "dangerous" only if it is exactly a system root:
      //   / | /* | ~/?(/*)? | $HOME/?(*?) | /[a-z]/?(*?) (git mount) | [A-Z]:[\\/]?(*)? (drive)
      const dangerousRoot = /^(\/\*?|~\/?\*?|\$HOME\/?\*?|\/[a-zA-Z]\/?\*?|[a-zA-Z]:[\\/]?\*?)$/i;
      for (const raw of args) {
        if (!raw || raw.startsWith('-')) continue;        // skip flags
        const arg = raw.replace(/^['"]|['"]$/g, '');       // strip one surrounding quote pair
        if (dangerousRoot.test(arg)) {
          return 'Blocked: recursive rm targets a system root ("' + arg + '"). Refusing catastrophic delete.';
        }
      }
    }
  }

  return null;
}
