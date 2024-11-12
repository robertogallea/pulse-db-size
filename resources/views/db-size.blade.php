@php
    $friendlySize = function(int $byte, int $precision = 0) {
        if ($byte >= 1024 * 1024 * 1024 * 1024) {
            return round($byte / 1024 / 1024 / 1024 / 1024, $precision) . 'TB';
        }
        if ($byte >= 1024 * 1024 * 1024) {
            return round($byte / 1024 / 1024 / 1024, $precision) . 'GB';
        }
        if ($byte >= 1024 * 1024) {
            return round($byte / 1024 / 1024, $precision) . 'MB';
        }
        if ($byte >= 1024) {
            return round($byte / 1024, $precision) . 'KB';
        }
        return round($byte, $precision) . 'B';
    };

    $cols = ! empty($cols) ? $cols : 'full';
    $rows = ! empty($rows) ? $rows : 1;
@endphp

<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
            name="DB Size"
            title="Time: {{ number_format($time) }}ms; Run at: {{ $runAt }};"
            details="past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.circle-stack/>
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($totalSize === 0 && $tableCount === 0)
            <x-pulse::no-results/>
        @else
            <div class="flex flex-col gap-6">
                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="flex flex-col justify-center @sm:block">
                        <span class="text-xl uppercase font-bold text-gray-700 dark:text-gray-300 tabular-nums">
                                {{ $friendlySize($totalSize, 2) }}
                        </span>
                        <span class="text-xs uppercase font-bold text-gray-500 dark:text-gray-400">
                            Total size
                        </span>
                    </div>
                    <div class="flex flex-col justify-center @sm:block">
                        <span class="text-xl uppercase font-bold text-gray-700 dark:text-gray-300 tabular-nums">
                                {{ $tableCount }}
                        </span>
                        <span class="text-xs uppercase font-bold text-gray-500 dark:text-gray-400">
                            # tables
                        </span>
                    </div>
                </div>
                <div>
                    <x-pulse::table>
                        <colgroup>
                            <col width="50%"/>
                            <col width="0%"/>
                            <col width="50%"/>
                        </colgroup>
                        <x-pulse::thead>
                            <tr>
                                <x-pulse::th>Table</x-pulse::th>
                                <x-pulse::th class="text-right">Size</x-pulse::th>
                                <x-pulse::th class="text-left">&nbsp;</x-pulse::th>
                            </tr>
                        </x-pulse::thead>
                        <tbody>
                        @foreach ($dbSize as $table => $size)
                            <tr wire:key="{{ $table }}-spacer" class="h-2 first:h-0"></tr>
                            <tr wire:key="{{ $table }}-row">
                                <x-pulse::td class="max-w-[1px]">
                                    <code class="block text-xs text-gray-900 dark:text-gray-100 truncate"
                                          title="{{ $table }}">
                                        {{ $table }}
                                    </code>
                                </x-pulse::td>
                                <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                    {{ $friendlySize($size, 2) }}
                                </x-pulse::td>
                                <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                    <div class="pr-2 bg-[#9333ea]" style="width: {{$size * 100 / $totalSize}}% !important;">&nbsp;</div>
                                </x-pulse::td>
                            </tr>
                        @endforeach
                        </tbody>
                    </x-pulse::table>
                </div>
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
