(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var config = window.sinemakuAllReportConfig || {};
    var colors = {
        brand: [47, 69, 88],
        accent: [153, 27, 27],
        primary: [38, 34, 94],
        purple: [98, 91, 214],
        green: [4, 120, 87],
        blue: [37, 99, 235],
        orange: [234, 88, 12],
        text: [31, 41, 55],
        muted: [107, 114, 128],
        border: [229, 231, 235],
        soft: [249, 250, 251]
    };

    function numberValue(value) {
        if (typeof value === 'number') {
            return Number.isFinite(value) ? value : 0;
        }

        var normalized = String(value || '0').replace(/,/g, '').replace(/%/g, '');
        var parsed = Number(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function reportNumber(value, decimals) {
        return numberValue(value).toLocaleString('id-ID', {
            minimumFractionDigits: decimals || 0,
            maximumFractionDigits: decimals || 0
        });
    }

    function reportPercent(value) {
        return reportNumber(value, 2) + '%';
    }

    function displayDate(value) {
        var match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? match[3] + '-' + match[2] + '-' + match[1] : (value || '-');
    }

    function selectedText(selector, fallback) {
        var $select = $(selector);
        var value = $select.val();
        return value ? ($select.find('option:selected').text() || fallback || '-') : (fallback || '-');
    }

    function activeFilters() {
        var defaults = config.defaults || {};
        var stored = window.sinemakuLatestDashboardFilters || {};

        return {
            nama_film: stored.nama_film || defaults.nama_film || '',
            tgl_mulai: stored.tgl_mulai || defaults.tgl_mulai || '',
            tgl_akhir: stored.tgl_akhir || defaults.tgl_akhir || '',
            bioskop_kategori: stored.bioskop_kategori || defaults.bioskop_kategori || 'ALL',
            kota: 'ALL',
            nama_bioskop: 'ALL',
            type_tiket: 'ALL'
        };
    }

    function filterLabels(filters) {
        var selectedFilmValue = $('#nama_film').val();
        var selectedCategoryValue = $('#bioskop_kategori').val();

        return {
            film: selectedFilmValue === filters.nama_film
                ? selectedText('#nama_film', filters.nama_film)
                : (filters.nama_film || '-'),
            period: displayDate(filters.tgl_mulai) + ' s.d. ' + displayDate(filters.tgl_akhir),
            category: selectedCategoryValue === filters.bioskop_kategori
                ? selectedText('#bioskop_kategori', 'Semua')
                : 'Semua'
        };
    }

    function fetchReport(url, params, label) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                data: params,
                success: function (response) {
                    // Beberapa konfigurasi middleware membungkus payload JSON di
                    // dalam `data`. Samakan bentuknya supaya seluruh report selalu
                    // menerima object response dari controller.
                    if (response && response.data && !response.daily && !response.summary && !response.rows) {
                        resolve(response.data);
                        return;
                    }

                    resolve(response);
                },
                error: function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Gagal mengambil ' + label + '.';
                    reject(new Error(message));
                }
            });
        });
    }

    function openDownloadProgress() {
        if (!window.Swal) {
            return;
        }

        Swal.fire({
            title: 'Menyiapkan Summary Report',
            position: 'top-end',
            toast: true,
            width: 390,
            html:
                '<div style="color:#6b7280;font-size:12px;margin-bottom:12px">' +
                    'Mengumpulkan seluruh data laporan. Mohon jangan menutup halaman.' +
                '</div>' +
                '<div style="height:10px;background:#ecebfa;border-radius:999px;overflow:hidden">' +
                    '<div id="summary-report-progress-bar" style="width:8%;height:100%;background:linear-gradient(90deg,#26225e,#716bd3);border-radius:999px;transition:width .3s ease"></div>' +
                '</div>' +
                '<div id="summary-report-progress-label" style="margin-top:9px;color:#374151;font-size:12px;font-weight:700">8% · Menyiapkan parameter laporan...</div>',
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    }

    function updateDownloadProgress(percent, label) {
        var progressBar = document.getElementById('summary-report-progress-bar');
        var progressLabel = document.getElementById('summary-report-progress-label');

        if (progressBar) {
            progressBar.style.width = Math.max(0, Math.min(100, percent)) + '%';
        }
        if (progressLabel) {
            progressLabel.textContent = Math.round(percent) + '% · ' + label;
        }
    }

    function showDownloadError(message) {
        if (window.Swal) {
            Swal.fire({
                type: 'error',
                title: 'Summary Report gagal dibuat',
                text: message || 'Silakan coba kembali.',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#26225e'
            });
            return;
        }

        alert(message || 'Summary Report gagal dibuat. Silakan coba kembali.');
    }

    function chartImage(key) {
        try {
            if (typeof chartInstances !== 'undefined' && chartInstances[key]) {
                return chartInstances[key].toBase64Image();
            }
        } catch (error) {
            console.warn('Gagal menyiapkan gambar grafik ' + key, error);
        }
        return null;
    }

    function createTrendImage(daily) {
        if (!window.Chart || !daily || !daily.length) {
            return null;
        }

        var canvas = document.createElement('canvas');
        canvas.width = 1500;
        canvas.height = 560;
        var chart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: daily.map(function (row) { return displayDate(row.tanggal); }),
                datasets: [
                    {
                        label: 'Total Production House',
                        data: daily.map(function (row) { return numberValue(row.total_ph); }),
                        borderColor: '#00a86b',
                        backgroundColor: '#00a86b',
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#00a86b',
                        pointBorderWidth: 2,
                        tension: 0.32,
                        fill: false,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Gross',
                        data: daily.map(function (row) { return numberValue(row.gross); }),
                        borderColor: '#f97316',
                        backgroundColor: '#f97316',
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#f97316',
                        pointBorderWidth: 2,
                        tension: 0.32,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Penonton',
                        data: daily.map(function (row) { return numberValue(row.audience); }),
                        borderColor: '#0284c7',
                        backgroundColor: '#0284c7',
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#0284c7',
                        pointBorderWidth: 2,
                        tension: 0.32,
                        yAxisID: 'audience'
                    }
                ]
            },
            options: {
                responsive: false,
                animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#374151', usePointStyle: true, padding: 24, font: { size: 15 } }
                    }
                },
                scales: {
                    x: { ticks: { color: '#6b7280', font: { size: 13 } }, grid: { color: 'rgba(107,114,128,0.10)' } },
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        ticks: { color: '#374151', font: { size: 13 } },
                        grid: { color: 'rgba(107,114,128,0.12)' }
                    },
                    audience: {
                        beginAtZero: true,
                        position: 'right',
                        ticks: { color: '#2563eb', font: { size: 13 } },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });

        var image = chart.toBase64Image();
        chart.destroy();
        return image;
    }

    function buildCombinedPdf(data, filters, labels) {
        var JsPdf = window.jspdf && window.jspdf.jsPDF;
        if (!JsPdf || !JsPdf.API.autoTable) {
            throw new Error('Library PDF belum berhasil dimuat. Silakan refresh halaman.');
        }

        var doc = new JsPdf('l', 'mm', 'a4');
        var pageW = doc.internal.pageSize.getWidth();
        var pageH = doc.internal.pageSize.getHeight();
        var marginX = 14;
        var usableW = pageW - (marginX * 2);
        var generatedDate = new Date();
        var generatedAt = generatedDate.toLocaleString('id-ID', {
            day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        function addLogo() {
            var logo = document.getElementById('report-logo');
            if (window.SinemakuPdfLogo && logo) {
                window.SinemakuPdfLogo.add(doc, logo, marginX, 8, 14, 14);
            }
        }

        function addHeader(section) {
            addLogo();
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(15);
            doc.setTextColor.apply(doc, colors.text);
            doc.text('SINEMAKU PICTURES', marginX + 18, 13);
            doc.setFontSize(9.5);
            doc.setTextColor.apply(doc, colors.accent);
            doc.text('Audience Analytics Dashboard', marginX + 18, 18);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8.2);
            doc.setTextColor.apply(doc, colors.muted);
            doc.text(section || 'All Reports', pageW - marginX, 13, { align: 'right' });
            doc.text('Generated: ' + generatedAt, pageW - marginX, 18, { align: 'right' });
            doc.setDrawColor.apply(doc, colors.accent);
            doc.setLineWidth(0.45);
            doc.line(marginX, 25, pageW - marginX, 25);
            doc.setDrawColor.apply(doc, colors.border);
            doc.setLineWidth(0.15);
            doc.line(marginX, 27, pageW - marginX, 27);
        }

        function addFooter() {
            var pages = doc.internal.getNumberOfPages();
            for (var page = 1; page <= pages; page++) {
                doc.setPage(page);
                doc.setDrawColor.apply(doc, colors.border);
                doc.line(marginX, pageH - 13, pageW - marginX, pageH - 13);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(8);
                doc.setTextColor.apply(doc, colors.muted);
                doc.text('Sinemaku Pictures - Confidential Analytics Report', marginX, pageH - 8);
                doc.text('Halaman ' + page + ' dari ' + pages, pageW - marginX, pageH - 8, { align: 'right' });
            }
        }

        function pageTitle(title, subtitle, section) {
            addHeader(section || title);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(15);
            doc.setTextColor.apply(doc, colors.text);
            doc.text(title, marginX, 35);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8.8);
            doc.setTextColor.apply(doc, colors.muted);
            doc.text(subtitle || '', marginX, 41, { maxWidth: usableW });
        }

        function nextPage(title, subtitle, section) {
            doc.addPage('a4', 'landscape');
            pageTitle(title, subtitle, section);
        }

        function filterBox(y) {
            var items = [
                ['Nama Film', labels.film],
                ['Periode', labels.period],
                ['Kategori Bioskop', labels.category]
            ];
            var width = usableW / items.length;
            doc.setFillColor.apply(doc, colors.soft);
            doc.setDrawColor.apply(doc, colors.border);
            doc.roundedRect(marginX, y, usableW, 23, 2, 2, 'FD');
            items.forEach(function (item, index) {
                var x = marginX + (width * index) + 5;
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(7.3);
                doc.setTextColor.apply(doc, colors.muted);
                doc.text(item[0].toUpperCase(), x, y + 8);
                doc.setFontSize(9);
                doc.setTextColor.apply(doc, colors.text);
                doc.text(String(item[1] || '-'), x, y + 15, { maxWidth: width - 10 });
            });
        }

        function metricCard(label, value, x, y, width, color) {
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor.apply(doc, colors.border);
            doc.roundedRect(x, y, width, 21, 2, 2, 'FD');
            doc.setFillColor.apply(doc, color);
            doc.roundedRect(x, y, 3, 21, 1.5, 1.5, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(11);
            doc.setTextColor.apply(doc, colors.text);
            doc.text(String(value), x + 7, y + 9, { maxWidth: width - 10 });
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7.5);
            doc.setTextColor.apply(doc, colors.muted);
            doc.text(label, x + 7, y + 16, { maxWidth: width - 10 });
        }

        function imagePanel(imageData, title, x, y, width, height) {
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor.apply(doc, colors.border);
            doc.roundedRect(x, y, width, height, 2, 2, 'FD');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(8.5);
            doc.setTextColor.apply(doc, colors.text);
            doc.text(title, x + 4, y + 7, { maxWidth: width - 8 });

            if (!imageData) {
                doc.setFont('helvetica', 'normal');
                doc.setTextColor.apply(doc, colors.muted);
                doc.text('Grafik tidak tersedia.', x + (width / 2), y + (height / 2), { align: 'center' });
                return;
            }

            var image = doc.getImageProperties(imageData);
            var ratio = Math.min((width - 8) / image.width, (height - 15) / image.height);
            var imageW = image.width * ratio;
            var imageH = image.height * ratio;
            doc.addImage(
                imageData,
                'PNG',
                x + ((width - imageW) / 2),
                y + 11 + ((height - 12 - imageH) / 2),
                imageW,
                imageH,
                undefined,
                'FAST'
            );
        }

        function insightBox(title, lines, y, height) {
            var boxHeight = height || 22;
            var normalizedLines = Array.isArray(lines) ? lines : [lines];
            doc.setFillColor(247, 247, 253);
            doc.setDrawColor(221, 219, 242);
            doc.roundedRect(marginX, y, usableW, boxHeight, 2, 2, 'FD');
            doc.setFillColor.apply(doc, colors.primary);
            doc.roundedRect(marginX, y, 3, boxHeight, 1.5, 1.5, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(8);
            doc.setTextColor.apply(doc, colors.primary);
            doc.text(title || 'Insight', marginX + 7, y + 7);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7.2);
            doc.setTextColor.apply(doc, colors.text);
            var maxLines = Math.max(1, Math.floor((boxHeight - 10) / 4));
            var renderedLines = [];
            normalizedLines.forEach(function (line) {
                renderedLines = renderedLines.concat(doc.splitTextToSize(String(line || '-'), usableW - 14));
            });
            renderedLines.slice(0, maxLines).forEach(function (line, index) {
                doc.text(line, marginX + 7, y + 13 + (index * 4));
            });
        }

        function addChartAndTable(options) {
            var labels = options.labels || [];
            var values = options.values || [];
            var total = values.reduce(function (sum, value) { return sum + numberValue(value); }, 0);
            var highestValue = values.length ? Math.max.apply(null, values.map(numberValue)) : 0;
            var highestIndex = values.map(numberValue).indexOf(highestValue);
            var primaryLabel = options.primaryLabel || labels[highestIndex] || '-';
            var primaryValue = typeof options.primaryValue !== 'undefined' ? options.primaryValue : highestValue;
            var insightLines = options.insights || [
                'Nilai utama: ' + primaryLabel + ' dengan ' + reportNumber(primaryValue, 0) + ' penonton.',
                'Total data pada grafik: ' + reportNumber(total, 0) + ' penonton dari ' + reportNumber(labels.length, 0) + ' kategori.'
            ];

            nextPage(options.title, options.subtitle, options.section || 'Chart Summary');
            imagePanel(options.image, options.chartTitle || options.title, marginX + 16, 48, usableW - 32, 110);
            insightBox('Insight Grafik', insightLines, 164, 23);

            nextPage(
                'Tabel ' + options.title,
                'Rincian angka yang membentuk grafik pada halaman sebelumnya.',
                (options.section || 'Chart Summary') + ' - Data'
            );
            var rows = labels.map(function (label, index) {
                var value = numberValue(values[index]);
                return [
                    index + 1,
                    String(label || '-').toUpperCase(),
                    reportNumber(value, 0),
                    total ? reportPercent((value / total) * 100) : '0,00%'
                ];
            });
            var compactTable = rows.length > 18;
            doc.autoTable({
                startY: 48,
                margin: { left: marginX, right: marginX, bottom: 18 },
                head: [['Rank', options.dimension || 'Category', options.valueLabel || 'Audience', 'Contribution']],
                body: rows,
                theme: 'grid',
                pageBreak: 'avoid',
                rowPageBreak: 'avoid',
                styles: {
                    font: 'helvetica',
                    fontSize: compactTable ? 6.5 : 7.5,
                    cellPadding: compactTable ? 1.15 : 2,
                    textColor: colors.text,
                    valign: 'middle'
                },
                headStyles: { fillColor: colors.brand, textColor: [255, 255, 255], fontStyle: 'bold' },
                alternateRowStyles: { fillColor: colors.soft },
                columnStyles: {
                    0: { cellWidth: 18, halign: 'center' },
                    1: { cellWidth: 'auto' },
                    2: { cellWidth: 48, halign: 'right' },
                    3: { cellWidth: 38, halign: 'right' }
                }
            });
        }

        var dashboard = data.dashboard || {};
        var metrics = dashboard.metrics || {};
        var finance = data.finance || {};
        var financeSummary = finance.summary || {};
        var trend = data.trend || {};
        var trendDaily = Array.isArray(trend.daily)
            ? trend.daily.slice()
            : (Array.isArray(trend.rows) ? trend.rows.slice() : []);
        var trendSummary = trend.summary || {};
        var detail = data.detail || {};
        var detailRows = detail.rows || [];
        var rekap = data.rekap || {};
        var rekapSummaryRows = Array.isArray(rekap.summary) ? rekap.summary : [];
        var rekapPerformanceRows = Array.isArray(rekap.performance) ? rekap.performance : [];
        var rekapProvinceRows = Array.isArray(rekap.province) ? rekap.province : [];
        var rekapAuditRows = Array.isArray(rekap.audit) ? rekap.audit : [];

        pageTitle('Summary Report', 'Ringkasan seluruh laporan dan detail berdasarkan satu filter aktif.', 'Executive Summary');
        filterBox(48);
        var gap = 4;
        var cardW = (usableW - (gap * 3)) / 4;
        metricCard('Total Penonton', reportNumber(metrics.audience || financeSummary.audience, 0), marginX, 79, cardW, colors.purple);
        metricCard('Jumlah Kota', reportNumber(metrics.cities || financeSummary.city_count, 0), marginX + cardW + gap, 79, cardW, colors.blue);
        metricCard('Jumlah Bioskop', reportNumber(metrics.cinemas || financeSummary.cinema_count, 0), marginX + ((cardW + gap) * 2), 79, cardW, colors.green);
        metricCard('Jumlah Show', reportNumber(metrics.shows, 0), marginX + ((cardW + gap) * 3), 79, cardW, colors.orange);
        metricCard('Gross', reportNumber(financeSummary.gross, 2), marginX, 104, cardW, colors.primary);
        metricCard('Net', reportNumber(financeSummary.net, 2), marginX + cardW + gap, 104, cardW, colors.green);
        metricCard('Total Production House', reportNumber(financeSummary.total_ph, 2), marginX + ((cardW + gap) * 2), 104, cardW, colors.purple);
        metricCard('Baris Detail', reportNumber(detail.row_count || detailRows.length, 0), marginX + ((cardW + gap) * 3), 104, cardW, colors.accent);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9.5);
        doc.setTextColor.apply(doc, colors.text);
        doc.text('CAKUPAN LAPORAN', marginX, 140);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor.apply(doc, colors.muted);
        doc.text([
            '1. Dashboard Audience Performance',
            '2. Grafik TOP 20 Kota',
            '3. Trend Analysis',
            '4. Daily Theatrical Performance Report',
            '5. Finance Insight',
            '6. Box Office Performance Summary'
        ], marginX, 148);

        var topCities = dashboard.top_cities || [];
        var shows = dashboard.shows_over_time || [];
        var cinemaGroups = dashboard.viewers_by_cinema || [];
        var topCinemas = dashboard.top_cinemas || [];
        var underCities = dashboard.underperf_cities || [];
        var underCinemas = dashboard.underperf_cinemas || [];

        nextPage('Dashboard Audience Performance', 'Ringkasan performa penonton berdasarkan filter aktif.', 'Dashboard Summary');
        metricCard('Penonton', reportNumber(metrics.audience, 0), marginX, 50, cardW, colors.purple);
        metricCard('Kota', reportNumber(metrics.cities, 0), marginX + cardW + gap, 50, cardW, colors.blue);
        metricCard('Show', reportNumber(metrics.shows, 0), marginX + ((cardW + gap) * 2), 50, cardW, colors.orange);
        metricCard('Bioskop', reportNumber(metrics.cinemas, 0), marginX + ((cardW + gap) * 3), 50, cardW, colors.green);
        insightBox('Insight Dashboard', [
            'Filter ini mencakup ' + reportNumber(metrics.cities, 0) + ' kota dan ' + reportNumber(metrics.cinemas, 0) + ' bioskop.',
            'Total penonton tercatat ' + reportNumber(metrics.audience, 0) + ' dari ' + reportNumber(metrics.shows, 0) + ' urutan show.'
        ], 82, 25);

        addChartAndTable({
            title: 'TOP 20 Kota',
            subtitle: 'Kota dengan jumlah penonton tertinggi berdasarkan filter aktif.',
            section: 'Dashboard / Grafik Kota',
            image: chartImage('topCities'),
            dimension: 'City',
            labels: topCities.map(function (row) { return row.kota; }),
            values: topCities.map(function (row) { return row.jumlah; })
        });
        addChartAndTable({
            title: 'Penonton per Show',
            subtitle: 'Distribusi penonton berdasarkan urutan show.',
            section: 'Dashboard Charts',
            image: chartImage('shows'),
            dimension: 'Show',
            labels: shows.map(function (row) { return row.show; }),
            values: shows.map(function (row) { return row.jumlah; })
        });
        addChartAndTable({
            title: 'Komposisi Jaringan Bioskop',
            subtitle: 'Kontribusi penonton berdasarkan jaringan bioskop.',
            section: 'Dashboard Charts',
            image: chartImage('viewersByCinema'),
            dimension: 'Cinema Network',
            labels: cinemaGroups.map(function (row) { return row.bioskop; }),
            values: cinemaGroups.map(function (row) { return row.penonton; })
        });
        addChartAndTable({
            title: 'TOP 20 Bioskop',
            subtitle: 'Bioskop dengan jumlah penonton tertinggi.',
            section: 'Dashboard Charts',
            image: chartImage('topCinemas'),
            dimension: 'Cinema Name',
            labels: topCinemas.map(function (row) { return row.bioskop; }),
            values: topCinemas.map(function (row) { return row.penonton; })
        });
        addChartAndTable({
            title: 'Underperforming Kota',
            subtitle: 'Kota dengan jumlah penonton terendah yang masih memiliki transaksi.',
            section: 'Dashboard Charts',
            image: chartImage('underCities'),
            dimension: 'City',
            labels: underCities.map(function (row) { return row.kota; }),
            values: underCities.map(function (row) { return row.penonton; }),
            primaryLabel: underCities.length ? underCities[0].kota : '-',
            primaryValue: underCities.length ? underCities[0].penonton : 0,
            insights: underCities.length ? [
                'Kota dengan penonton terendah adalah ' + String(underCities[0].kota || '-').toUpperCase() + ' dengan ' + reportNumber(underCities[0].penonton, 0) + ' penonton.',
                'Data ini dapat digunakan untuk mengevaluasi jadwal, promosi, dan coverage kota.'
            ] : ['Belum ada data underperforming kota pada filter aktif.']
        });
        addChartAndTable({
            title: 'Underperforming Bioskop',
            subtitle: 'Bioskop dengan jumlah penonton terendah yang masih memiliki transaksi.',
            section: 'Dashboard Charts',
            image: chartImage('underCinemas'),
            dimension: 'Cinema Name',
            labels: underCinemas.map(function (row) { return row.nama_bioskop; }),
            values: underCinemas.map(function (row) { return row.penonton; }),
            primaryLabel: underCinemas.length ? underCinemas[0].nama_bioskop : '-',
            primaryValue: underCinemas.length ? underCinemas[0].penonton : 0,
            insights: underCinemas.length ? [
                'Bioskop dengan penonton terendah adalah ' + String(underCinemas[0].nama_bioskop || '-').toUpperCase() + ' dengan ' + reportNumber(underCinemas[0].penonton, 0) + ' penonton.',
                'Gunakan data ini untuk meninjau performa lokasi dan efektivitas jadwal tayang.'
            ] : ['Belum ada data underperforming bioskop pada filter aktif.']
        });

        function addBoxOfficeSection() {
            var totals = rekapSummaryRows.reduce(function (result, row) {
                result.audience += numberValue(row.jumlah);
                result.seats += numberValue(row.seats_available);
                result.gross += numberValue(row.gross);
                result.tax += numberValue(row.tax);
                result.net += numberValue(row.net);
                result.share += numberValue(row.share);
                result.totalPh += numberValue(row.total);
                return result;
            }, { audience: 0, seats: 0, gross: 0, tax: 0, net: 0, share: 0, totalPh: 0 });

            if (!rekapSummaryRows.length) {
                totals.audience = numberValue(financeSummary.audience);
                totals.seats = numberValue(financeSummary.seats_available);
                totals.gross = numberValue(financeSummary.gross);
                totals.tax = numberValue(financeSummary.tax);
                totals.net = numberValue(financeSummary.net);
                totals.share = numberValue(financeSummary.share);
                totals.totalPh = numberValue(financeSummary.total_ph);
            }

            totals.royalty = totals.share * 0.015;
            var occupancy = totals.seats ? (totals.audience / totals.seats) * 100 : 0;
            var atp = totals.audience ? totals.gross / totals.audience : 0;

            nextPage('Box Office Performance Summary', 'Revenue waterfall berdasarkan data lengkap modul Rekap Omset.', 'Box Office Performance Summary');
            metricCard('Gross', reportNumber(totals.gross, 2), marginX, 50, cardW, colors.primary);
            metricCard('Tax', reportNumber(totals.tax, 2), marginX + cardW + gap, 50, cardW, colors.accent);
            metricCard('Net', reportNumber(totals.net, 2), marginX + ((cardW + gap) * 2), 50, cardW, colors.green);
            metricCard('Total Production House', reportNumber(totals.totalPh, 2), marginX + ((cardW + gap) * 3), 50, cardW, colors.purple);
            metricCard('ATP', reportNumber(atp, 2), marginX, 76, cardW, colors.orange);
            metricCard('Occupancy', reportPercent(occupancy), marginX + cardW + gap, 76, cardW, colors.blue);
            metricCard('Share Production House', reportNumber(totals.share, 2), marginX + ((cardW + gap) * 2), 76, cardW, colors.primary);
            metricCard('Royalty 1.5%', reportNumber(totals.royalty, 2), marginX + ((cardW + gap) * 3), 76, cardW, colors.accent);

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9);
            doc.setTextColor.apply(doc, colors.text);
            doc.text('REVENUE WATERFALL', marginX, 105);

            var waterfall = [
                { label: 'Gross Box Office', value: totals.gross, color: [124, 58, 237] },
                { label: 'Tax', value: totals.tax, prefix: '-', color: [220, 38, 38] },
                { label: 'Net Box Office', value: totals.net, color: [2, 132, 199] },
                { label: 'Share 50%', value: totals.share, prefix: '-', color: [234, 88, 12] },
                { label: 'Royalty 1.5%', value: totals.royalty, prefix: '-', color: [219, 39, 119] },
                { label: 'Total Production House', value: totals.totalPh, color: [5, 150, 105] }
            ];
            var maximumWaterfall = Math.max(totals.gross, 1);
            var waterfallLabelW = 45;
            var waterfallValueW = 50;
            var waterfallTrackX = marginX + waterfallLabelW;
            var waterfallTrackW = usableW - waterfallLabelW - waterfallValueW;

            waterfall.forEach(function (step, index) {
                var y = 110 + (index * 8.2);
                var barW = Math.max(1.5, waterfallTrackW * (Math.abs(step.value) / maximumWaterfall));
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(7.2);
                doc.setTextColor.apply(doc, colors.text);
                doc.text(step.label, marginX, y + 4.8, { maxWidth: waterfallLabelW - 3 });
                doc.setFillColor(243, 244, 246);
                doc.roundedRect(waterfallTrackX, y, waterfallTrackW, 6, 2, 2, 'F');
                doc.setFillColor.apply(doc, step.color);
                doc.roundedRect(waterfallTrackX, y, Math.min(barW, waterfallTrackW), 6, 2, 2, 'F');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(7);
                doc.setTextColor.apply(doc, colors.text);
                doc.text((step.prefix || '') + reportNumber(step.value, 2), pageW - marginX, y + 4.7, { align: 'right' });
            });

            insightBox('Insight Box Office Performance', [
                'Gross sebesar ' + reportNumber(totals.gross, 2) + ' menghasilkan Net sebesar ' + reportNumber(totals.net, 2) + '.',
                'Setelah Share Production House dan Royalty, estimasi Total Akhir adalah ' + reportNumber(totals.totalPh, 2) + '.'
            ], 162, 24);

            function plainText(value) {
                return $('<div>').html(String(value || '-')).text();
            }

            function addRekapTable(title, headers, rows, columnStyles, fontSize) {
                var tableRows = rows.length ? rows : [[{
                    content: 'No data available for the selected filters.',
                    colSpan: headers.length,
                    styles: { halign: 'center', textColor: colors.muted }
                }]];

                doc.addPage('a4', 'landscape');
                doc.autoTable({
                    startY: 43,
                    margin: { top: 43, left: marginX, right: marginX, bottom: 18 },
                    head: [headers],
                    body: tableRows,
                    theme: 'grid',
                    showHead: 'everyPage',
                    pageBreak: 'auto',
                    rowPageBreak: 'avoid',
                    styles: {
                        font: 'helvetica',
                        fontSize: fontSize,
                        cellPadding: 1.1,
                        textColor: colors.text,
                        overflow: 'linebreak',
                        valign: 'middle'
                    },
                    headStyles: { fillColor: colors.brand, textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center' },
                    alternateRowStyles: { fillColor: colors.soft },
                    columnStyles: columnStyles,
                    didDrawPage: function () {
                        addHeader('Box Office Performance Summary - Data');
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(10.5);
                        doc.setTextColor.apply(doc, colors.text);
                        doc.text(title, marginX, 35);
                    }
                });
            }

            addRekapTable(
                'Summary by Cinema Category',
                ['No', 'Category', 'Audience', 'Available Capacity', 'Occupancy', 'Gross', 'ATP', 'Effective Tax Rate', 'Net', 'Share 50%', 'Royalty', 'Final Total'],
                rekapSummaryRows.map(function (row, index) {
                    return [index + 1, row.kategori || '-', row.jumlah || '0', row.seats_available || '0', row.occupancy_rate || '0.00%', row.gross || '0', row.atp || '0', row.effective_tax_rate || '0.00%', row.net || '0', row.share || '0', row.royalty || '1.5%', row.total || '0'];
                }),
                { 0: { cellWidth: 8 }, 1: { cellWidth: 24 }, 2: { cellWidth: 19 }, 3: { cellWidth: 24 }, 4: { cellWidth: 18 }, 5: { cellWidth: 28 }, 6: { cellWidth: 21 }, 7: { cellWidth: 21 }, 8: { cellWidth: 27 }, 9: { cellWidth: 26 }, 10: { cellWidth: 16 }, 11: { cellWidth: 28 } },
                5.8
            );

            addRekapTable(
                'Cinema Performance Ranking',
                ['Rank', 'City', 'Cinema Name', 'Audience', 'Available Capacity', 'Occupancy', 'Gross', 'ATP', 'Net', 'Total Production House'],
                rekapPerformanceRows.map(function (row, index) {
                    return [index + 1, row.kota || '-', String(row.nama_bioskop || '-').toUpperCase(), row.jumlah || '0', row.seats_available || '0', row.occupancy_rate || '0.00%', row.gross || '0', row.atp || '0', row.net || '0', row.total_ph || '0'];
                }),
                { 0: { cellWidth: 10 }, 1: { cellWidth: 22 }, 2: { cellWidth: 45 }, 3: { cellWidth: 20 }, 4: { cellWidth: 25 }, 5: { cellWidth: 20 }, 6: { cellWidth: 30 }, 7: { cellWidth: 24 }, 8: { cellWidth: 30 }, 9: { cellWidth: 35 } },
                rekapPerformanceRows.length > 35 ? 5.5 : 6.3
            );

            addRekapTable(
                'Province Performance Leaderboard',
                ['Rank', 'Province', 'Cities', 'Cinemas', 'Audience', 'Available Capacity', 'Occupancy', 'Gross', 'ATP', 'Effective Tax Rate', 'Net', 'Total Production House'],
                rekapProvinceRows.map(function (row, index) {
                    return [index + 1, row.provinsi || '-', row.city_count || '0', row.cinema_count || '0', row.jumlah || '0', row.seats_available || '0', row.occupancy_rate || '0.00%', row.gross || '0', row.atp || '0', row.effective_tax_rate || '0.00%', row.net || '0', row.total_ph || '0'];
                }),
                { 0: { cellWidth: 8 }, 1: { cellWidth: 29 }, 2: { cellWidth: 14 }, 3: { cellWidth: 16 }, 4: { cellWidth: 19 }, 5: { cellWidth: 24 }, 6: { cellWidth: 18 }, 7: { cellWidth: 27 }, 8: { cellWidth: 21 }, 9: { cellWidth: 21 }, 10: { cellWidth: 28 }, 11: { cellWidth: 30 } },
                rekapProvinceRows.length > 30 ? 5.4 : 6
            );

            addRekapTable(
                'Audit Checks',
                ['No', 'Issue', 'Date', 'Category', 'City', 'Cinema Name', 'Studio', 'Show', 'Ticket Type', 'Audience', 'Capacity', 'Price', 'Gross', 'Expected Gross', 'Variance', 'Tax'],
                rekapAuditRows.map(function (row, index) {
                    return [index + 1, plainText(row.issue), displayDate(row.tgl_tayang), row.kategori || '-', row.kota || '-', String(row.nama_bioskop || '-').toUpperCase(), row.studio || '-', row.show || '-', row.type_tiket || '-', row.jumlah || '0', row.kapasitas || '0', row.harga || '0', row.gross || '0', row.expected_gross || '0', row.selisih || '0', row.pajak || '-'];
                }),
                { 0: { cellWidth: 7 }, 1: { cellWidth: 30 }, 2: { cellWidth: 15 }, 3: { cellWidth: 18 }, 4: { cellWidth: 15 }, 5: { cellWidth: 28 }, 6: { cellWidth: 12 }, 7: { cellWidth: 10 }, 8: { cellWidth: 18 }, 9: { cellWidth: 14 }, 10: { cellWidth: 14 }, 11: { cellWidth: 16 }, 12: { cellWidth: 21 }, 13: { cellWidth: 21 }, 14: { cellWidth: 16 }, 15: { cellWidth: 11 } },
                4.8
            );
        }

        function addFinanceSection() {
            nextPage('Finance Insight', 'Executive insight, provinsi teratas, dan bioskop teratas.', 'Finance Insight Summary');
            var financeNotes = finance.notes && finance.notes.length ? finance.notes : ['Tidak ada catatan khusus.'];
        var provinceRows = (finance.province_leaderboard || []).map(function (row, index) {
            return [index + 1, row.provinsi || '-', reportNumber(row.city_count, 0), reportNumber(row.cinema_count, 0), reportNumber(row.audience, 0), reportNumber(row.gross, 2), reportNumber(row.total_ph, 2)];
        });
        doc.autoTable({
            startY: 48,
            margin: { left: marginX, right: marginX, bottom: 18 },
            head: [['Rank', 'Province', 'Cities', 'Cinemas', 'Audience', 'Gross', 'Total Production House']],
            body: provinceRows,
            theme: 'grid',
            styles: { font: 'helvetica', fontSize: 7, cellPadding: 1.5, textColor: colors.text },
            headStyles: { fillColor: colors.brand, textColor: [255, 255, 255], fontStyle: 'bold' },
            alternateRowStyles: { fillColor: colors.soft }
        });
        var cinemaRows = (finance.leaderboard || []).map(function (row, index) {
            return [index + 1, String(row.nama_bioskop || '-').toUpperCase(), row.kota || '-', reportNumber(row.audience, 0), reportNumber(row.gross, 2), reportNumber(row.total_ph, 2)];
        });
        doc.autoTable({
            startY: doc.lastAutoTable.finalY + 7,
            margin: { left: marginX, right: marginX, bottom: 18 },
            head: [['Rank', 'Cinema Name', 'City', 'Audience', 'Gross', 'Total Production House']],
            body: cinemaRows,
            theme: 'grid',
            styles: { font: 'helvetica', fontSize: 7, cellPadding: 1.5, textColor: colors.text },
            headStyles: { fillColor: colors.brand, textColor: [255, 255, 255], fontStyle: 'bold' },
            alternateRowStyles: { fillColor: colors.soft }
        });
            insightBox('Management Insight', financeNotes.slice(0, 5), doc.lastAutoTable.finalY + 5, 32);
        }

        nextPage('Trend Analysis', 'Pergerakan Total Production House, Gross, dan Penonton dari hari ke hari.', 'Trend Analysis Summary');
        var trendImage = createTrendImage(trendDaily);
        imagePanel(trendImage, 'Daily Trend Chart', marginX + 16, 48, usableW - 32, 110);
        var trendNotes = trend.notes && trend.notes.length ? trend.notes : ['Belum cukup data untuk membaca tren.'];
        insightBox('Insight Trend Analysis', trendNotes.slice(0, 2), 164, 23);

        nextPage('Tabel Trend Analysis', 'Rincian angka utama yang membentuk Daily Trend Chart.', 'Trend Analysis - Data');
        var trendRows = trendDaily.map(function (row) {
            return [
                displayDate(row.tanggal),
                reportNumber(row.total_ph, 2),
                reportNumber(row.gross, 2),
                reportNumber(row.audience, 0)
            ];
        });
        var trendCompact = trendRows.length > 26;
        doc.autoTable({
            startY: 48,
            margin: { left: marginX, right: marginX, bottom: 18 },
            head: [['Date', 'Total Production House', 'Gross', 'Audience']],
            body: trendRows,
            theme: 'grid',
            pageBreak: 'auto',
            rowPageBreak: 'avoid',
            showHead: 'everyPage',
            styles: {
                font: 'helvetica',
                fontSize: trendCompact ? 5.8 : 7.2,
                cellPadding: trendCompact ? 0.75 : 1.7,
                textColor: colors.text,
                valign: 'middle'
            },
            headStyles: { fillColor: colors.brand, textColor: [255, 255, 255], fontStyle: 'bold' },
            alternateRowStyles: { fillColor: colors.soft },
            columnStyles: {
                0: { cellWidth: 42 },
                1: { halign: 'right', cellWidth: 65 },
                2: { halign: 'right', cellWidth: 65 },
                3: { halign: 'right', cellWidth: 'auto' }
            }
        });

        doc.addPage('a4', 'landscape');
        var pdfDetailRows = detailRows.map(function (row) {
            return [
                displayDate(row.tgl_tayang),
                row.kota || '-',
                String(row.nama_bioskop || '-').toUpperCase(),
                reportNumber(row.Total, 0),
                reportPercent(row.occupancy_rate),
                reportNumber(row.harga, 2),
                reportNumber(row.gross, 2),
                reportPercent(row.pajak_persen),
                reportNumber(row.pajak, 2),
                reportNumber(row.net, 2),
                reportNumber(row.share_ph, 2),
                reportNumber(row.royalty, 2),
                reportNumber(row.total_akhir, 2)
            ];
        });
        doc.autoTable({
            startY: 43,
            margin: { top: 43, left: marginX, right: marginX, bottom: 18 },
            head: [[
                'Date', 'City', 'Cinema Name', 'Total Audience', 'Occupancy', 'Price', 'Gross',
                'Tax %', 'Tax', 'Net', 'Share Production House', 'Royalty (1.5%)', 'Final Total'
            ]],
            body: pdfDetailRows,
            theme: 'grid',
            showHead: 'everyPage',
            styles: { font: 'helvetica', fontSize: 6, cellPadding: 1.25, textColor: colors.text, overflow: 'linebreak', valign: 'middle' },
            headStyles: { fillColor: colors.brand, textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center' },
            alternateRowStyles: { fillColor: colors.soft },
            columnStyles: {
                0: { cellWidth: 17 },
                1: { cellWidth: 20 },
                2: { cellWidth: 35 },
                3: { halign: 'right', cellWidth: 20 },
                4: { halign: 'right', cellWidth: 15 },
                5: { halign: 'right', cellWidth: 17 },
                6: { halign: 'right', cellWidth: 23 },
                7: { halign: 'right', cellWidth: 11 },
                8: { halign: 'right', cellWidth: 21 },
                9: { halign: 'right', cellWidth: 22 },
                10: { halign: 'right', cellWidth: 22 },
                11: { halign: 'right', cellWidth: 22 },
                12: { halign: 'right', cellWidth: 24 }
            },
            didDrawPage: function () {
                addHeader('Daily Theatrical Performance Report');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(10.5);
                doc.setTextColor.apply(doc, colors.text);
                doc.text('Daily Theatrical Performance Report', marginX, 35);
            }
        });

        // Bagian finansial ditempatkan paling akhir setelah laporan detail.
        addFinanceSection();
        addBoxOfficeSection();

        addFooter();
        var safeFilm = String(labels.film || 'semua-film').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        doc.save('summary-report-' + (safeFilm || 'semua-film') + '.pdf');
    }

    $(document).on('click', '#download-all-reports', function () {
        var $button = $(this);
        var originalHtml = $button.html();
        var filters = activeFilters();

        if (!filters.nama_film || !filters.tgl_mulai || !filters.tgl_akhir) {
            alert('Film dan periode laporan belum tersedia. Silakan jalankan filter terlebih dahulu.');
            return;
        }

        if (!window.sinemakuLatestDashboardPayload) {
            alert('Data Dashboard belum selesai dimuat. Silakan coba kembali.');
            return;
        }

        $button.prop('disabled', true).html('<i class="fal fa-spinner fa-spin mr-1"></i> Menyiapkan report...');
        openDownloadProgress();

        var commonParams = $.extend({}, filters);
        var completedSources = 0;
        var markSourceComplete = function (label) {
            completedSources += 1;
            updateDownloadProgress(10 + (completedSources * 10), label + ' selesai dimuat.');
        };
        var financeRequest = fetchReport(config.financeUrl, commonParams, 'Finance Insight').then(function (response) {
            markSourceComplete('Finance Insight');
            return response;
        });
        var trendRequest = fetchReport(config.trendUrl, commonParams, 'Trend Analysis').then(function (response) {
            markSourceComplete('Trend Analysis');
            return response;
        });
        var detailRequest = fetchReport(
            config.detailUrl,
            $.extend({}, commonParams, { format: 'json' }),
            'Report Detail'
        ).then(function (response) {
            markSourceComplete('Report Detail');
            return response;
        });
        var rekapSummaryRequest = fetchReport(config.rekapSummaryUrl, commonParams, 'Rekap Omset Summary').then(function (response) {
            markSourceComplete('Rekap Omset Summary');
            return response;
        });
        var rekapPerformanceRequest = fetchReport(config.rekapPerformanceUrl, commonParams, 'Performance Ranking').then(function (response) {
            markSourceComplete('Performance Ranking');
            return response;
        });
        var rekapProvinceRequest = fetchReport(config.rekapProvinceUrl, commonParams, 'Province Leaderboard').then(function (response) {
            markSourceComplete('Province Leaderboard');
            return response;
        });
        var rekapAuditRequest = fetchReport(config.rekapAuditUrl, commonParams, 'Audit Checks').then(function (response) {
            markSourceComplete('Audit Checks');
            return response;
        });

        Promise.all([
            financeRequest,
            trendRequest,
            detailRequest,
            rekapSummaryRequest,
            rekapPerformanceRequest,
            rekapProvinceRequest,
            rekapAuditRequest
        ])
            .then(function (responses) {
                updateDownloadProgress(82, 'Menyusun halaman dan tabel PDF...');
                buildCombinedPdf({
                    dashboard: window.sinemakuLatestDashboardPayload,
                    finance: responses[0],
                    trend: responses[1],
                    detail: responses[2],
                    rekap: {
                        summary: responses[3],
                        performance: responses[4],
                        province: responses[5],
                        audit: responses[6]
                    }
                }, filters, filterLabels(filters));
                updateDownloadProgress(100, 'Summary Report selesai. Download dimulai...');

                return new Promise(function (resolve) {
                    window.setTimeout(resolve, 500);
                });
            })
            .then(function () {
                if (window.Swal) {
                    Swal.close();
                }
            })
            .catch(function (error) {
                console.error(error);
                showDownloadError(error.message || 'Gagal membuat Summary Report PDF. Silakan coba kembali.');
            })
            .then(function () {
                $button.prop('disabled', false).html(originalHtml);
            });
    });
})(window, window.jQuery);
