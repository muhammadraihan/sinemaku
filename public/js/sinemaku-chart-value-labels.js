(function (window) {
    'use strict';

    function fullNumber(value) {
        return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function compactNumber(value) {
        var number = Number(value || 0);
        var absolute = Math.abs(number);

        if (absolute >= 1000000000) {
            return (number / 1000000000).toFixed(1).replace('.0', '') + 'B';
        }
        if (absolute >= 1000000) {
            return (number / 1000000).toFixed(1).replace('.0', '') + 'M';
        }
        if (absolute >= 1000) {
            return (number / 1000).toFixed(1).replace('.0', '') + 'K';
        }

        return fullNumber(number);
    }

    function drawBarLabels(chart, context) {
        if (chart.options.indexAxis !== 'y') {
            return;
        }

        context.font = '700 11px Arial';
        context.textBaseline = 'middle';

        chart.data.datasets.forEach(function (dataset, datasetIndex) {
            var bars = chart.getDatasetMeta(datasetIndex).data || [];
            var values = dataset.data || [];

            bars.forEach(function (bar, index) {
                var label = fullNumber(values[index]);
                var labelWidth = context.measureText(label).width;
                var outsideX = bar.x + 7;
                var canFitOutside = outsideX + labelWidth <= chart.chartArea.right;

                context.textAlign = canFitOutside ? 'left' : 'right';
                context.fillStyle = canFitOutside ? '#374151' : '#ffffff';
                context.fillText(label, canFitOutside ? outsideX : bar.x - 6, bar.y);
            });
        });
    }

    function drawLineLabels(chart, context) {
        var offsets = [-11, 12, -23, 24];
        context.font = '700 ' + (chart.width < 600 ? 9 : 10) + 'px Arial';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.lineWidth = 3;
        context.strokeStyle = 'rgba(255,255,255,0.96)';

        chart.data.datasets.forEach(function (dataset, datasetIndex) {
            var points = chart.getDatasetMeta(datasetIndex).data || [];
            var values = dataset.data || [];
            var offset = offsets[datasetIndex % offsets.length];

            points.forEach(function (point, index) {
                var value = Number(values[index]);
                if (!Number.isFinite(value)) {
                    return;
                }

                var label = compactNumber(value);
                var y = Math.max(chart.chartArea.top + 7, Math.min(chart.chartArea.bottom - 7, point.y + offset));
                context.fillStyle = dataset.borderColor || '#374151';
                context.strokeText(label, point.x, y);
                context.fillText(label, point.x, y);
            });
        });
    }

    function drawDoughnutLabels(chart, context) {
        context.font = '700 ' + (chart.width < 600 ? 9 : 11) + 'px Arial';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.lineWidth = 3;
        context.strokeStyle = 'rgba(31,41,55,0.70)';
        context.fillStyle = '#ffffff';

        chart.data.datasets.forEach(function (dataset, datasetIndex) {
            var arcs = chart.getDatasetMeta(datasetIndex).data || [];
            var values = dataset.data || [];

            arcs.forEach(function (arc, index) {
                var value = Number(values[index] || 0);
                if (value <= 0) {
                    return;
                }

                var center = arc.getCenterPoint();
                var label = compactNumber(value);
                context.strokeText(label, center.x, center.y);
                context.fillText(label, center.x, center.y);
            });
        });
    }

    window.SinemakuChartValueLabels = {
        id: 'sinemakuChartValueLabels',
        afterDatasetsDraw: function (chart, args, options) {
            if (options && options.display === false) {
                return;
            }

            var type = chart.config.type;
            var context = chart.ctx;
            context.save();

            if (type === 'bar') {
                drawBarLabels(chart, context);
            } else if (type === 'line') {
                drawLineLabels(chart, context);
            } else if (type === 'doughnut' || type === 'pie') {
                drawDoughnutLabels(chart, context);
            }

            context.restore();
        }
    };
})(window);
