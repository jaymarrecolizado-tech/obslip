<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter form (fields are live, so results update on change) --}}
        {{ $this->form }}

        {{-- Summary cards --}}
        @php
            $results = $this->results;
            $statusCounts = $results->groupBy(fn ($s) => $s->status->value);
            $summary = [
                'Total Slips' => $results->count(),
                'Approved' => ($statusCounts['approved'] ?? collect())->count(),
                'Completed' => ($statusCounts['completed'] ?? collect())->count(),
                'Cancelled' => ($statusCounts['cancelled'] ?? collect())->count(),
            ];
        @endphp
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($summary as $label => $count)
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ $count }}</p>
                </div>
            @endforeach
        </div>

        <div class="flex justify-end">
            <x-filament::button color="primary" wire:click="exportCsv" icon="heroicon-o-arrow-down-tray">
                Export CSV
            </x-filament::button>
        </div>

        {{-- Results table --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Slip #</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Employee(s)</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($results as $slip)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $slip->slip_number }}</td>
                            <td class="px-4 py-3">{{ $slip->date?->format('M d, Y') }}</td>
                            <td class="px-4 py-3">{{ $slip->employees->pluck('full_name')->implode(', ') }}</td>
                            <td class="px-4 py-3">{{ $slip->department?->name }}</td>
                            <td class="px-4 py-3">{{ $slip->status?->label() }}</td>
                            <td class="px-4 py-3">{{ $slip->duration_hours ? number_format((float) $slip->duration_hours, 2) . 'h' : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No pass slips match the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
