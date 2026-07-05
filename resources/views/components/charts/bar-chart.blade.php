@props([
    'id' => null,
    'labels' => [],
    'datasets' => [],
    'height' => 300,
    'title' => null,
    'subtitle' => null,
    'showLegend' => true,
    'stacked' => false,
    'horizontal' => false,
    'class' => null,
])

@php
    $id = $id ?? 'bar-chart-' . Str::random(8);
    $height = $height ?? 300;
@endphp

<div class="chart-container {{ $class }}" style="height: {{ $height }}px;">
    @if ($title || $subtitle)
        <div class="chart-header mb-3">
            @if ($title)
                <h6 class="chart-title fw-semibold mb-0">{{ $title }}</h6>
            @endif
            @if ($subtitle)
                <span class="chart-subtitle text-muted small">{{ $subtitle }}</span>
            @endif
        </div>
    @endif

    <canvas id="{{ $id }}"></canvas>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('{{ $id }}');
            if (!ctx || typeof Chart === 'undefined') return;

            const isDark = document.documentElement.classList.contains('theme-dark');
            const textColor = isDark ? '#e0e0e0' : '#6b7280';
            const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';

            const data = {
                labels: {!! json_encode($labels) !!},
                datasets: {!! json_encode($datasets) !!}
            };

            const options = {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: {{ $horizontal ? "'y'" : "'x'" }},
                plugins: {
                    legend: {
                        display: {{ $showLegend ? 'true' : 'false' }},
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(26,31,46,0.9)' : 'rgba(255,255,255,0.9)',
                        titleColor: isDark ? '#e0e0e0' : '#17202a',
                        bodyColor: isDark ? '#9ca3af' : '#6b7280',
                        borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 12
                    }
                },
                scales: {
                    x: {
                        stacked: {{ $stacked ? 'true' : 'false' }},
                        grid: {
                            color: gridColor,
                            drawBorder: true,
                            borderColor: gridColor
                        },
                        ticks: {
                            color: textColor
                        }
                    },
                    y: {
                        stacked: {{ $stacked ? 'true' : 'false' }},
                        beginAtZero: true,
                        grid: {
                            color: gridColor,
                            drawBorder: true,
                            borderColor: gridColor
                        },
                        ticks: {
                            color: textColor,
                            callback: value => value.toLocaleString()
                        }
                    }
                }
            };

            const chart = new Chart(ctx, {
                type: 'bar',
                data,
                options
            });
            window.charts = window.charts || {};
            window.charts['{{ $id }}'] = chart;

            document.addEventListener('themeChange', function() {
                const isDark = document.documentElement.classList.contains('theme-dark');
                const textColor = isDark ? '#e0e0e0' : '#6b7280';
                const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';

                chart.options.scales.x.ticks.color = textColor;
                chart.options.scales.x.grid.color = gridColor;
                chart.options.scales.y.ticks.color = textColor;
                chart.options.scales.y.grid.color = gridColor;
                if (chart.options.plugins?.legend) {
                    chart.options.plugins.legend.labels.color = textColor;
                }
                chart.update();
            });
        });
    </script>
@endpush
