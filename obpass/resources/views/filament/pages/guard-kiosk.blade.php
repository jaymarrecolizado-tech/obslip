<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Search --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="mb-4 text-xl font-semibold">Search Pass Slip</h2>
            <form wire:submit="searchSlip">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="flex-1">
                        {{ $this->form }}
                    </div>
                    <x-filament::button type="submit" size="lg" icon="heroicon-o-magnifying-glass">
                        Search
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Found slip card --}}
        @if ($this->found_slip)
            @php $slip = $this->found_slip; @endphp
            <div class="rounded-2xl border-2 border-primary-200 bg-primary-50 p-6 dark:border-primary-800 dark:bg-primary-950/40">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Slip Number</p>
                        <p class="text-2xl font-bold">{{ $slip->slip_number }}</p>
                    </div>
                    <span class="rounded-full bg-primary px-4 py-1 text-sm font-semibold text-white">
                        {{ $slip->status?->label() }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Employee</dt>
                        <dd class="font-medium">{{ $slip->employees->pluck('full_name')->implode(', ') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Department</dt>
                        <dd class="font-medium">{{ $slip->department?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Date</dt>
                        <dd class="font-medium">{{ $slip->date?->format('M d, Y') }}</dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button color="info" size="lg" icon="heroicon-o-arrow-right-circle" wire:click="logDeparture">
                        Log Departure
                    </x-filament::button>
                    <x-filament::button color="success" size="lg" icon="heroicon-o-arrow-left-circle" wire:click="logArrival">
                        Log Arrival
                    </x-filament::button>
                </div>
            </div>
        @endif

        {{-- Today's activity --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="mb-4 text-xl font-semibold">Today's Activity</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-base">
                    <thead class="border-b text-sm uppercase text-gray-500">
                        <tr>
                            <th class="py-3 pr-4">Slip #</th>
                            <th class="py-3 pr-4">Employee</th>
                            <th class="py-3 pr-4">Department</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Departure</th>
                            <th class="py-3">Arrival</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->todays_activity as $activity)
                            <tr>
                                <td class="py-3 pr-4 font-semibold">{{ $activity->slip_number }}</td>
                                <td class="py-3 pr-4">{{ $activity->employees->pluck('full_name')->implode(', ') }}</td>
                                <td class="py-3 pr-4">{{ $activity->department?->name }}</td>
                                <td class="py-3 pr-4">{{ $activity->status?->label() }}</td>
                                <td class="py-3 pr-4">{{ $activity->departure_time?->format('h:i A') ?? '—' }}</td>
                                <td class="py-3">{{ $activity->arrival_time?->format('h:i A') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-500">No active pass slips today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
