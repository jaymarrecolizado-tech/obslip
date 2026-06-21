#!/usr/bin/env bash
# PreToolUse hook entry point (matcher: Bash): block destructive commands.
#
# jq is not installed on this machine, so stdin JSON parsing + the deny rules
# run in node (v24 is present). The matching logic lives in guard-destructive.js
# next to this script. On any error or non-match the script prints nothing and
# exits 0, so it never breaks Claude's normal permission flow.
#
# Resolve the sibling .js relative to this script so it works regardless of cwd.
exec node "$(dirname "$0")/guard-destructive.js"
