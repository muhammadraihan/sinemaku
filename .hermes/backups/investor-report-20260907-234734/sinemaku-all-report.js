(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var config = window.sinemakuAllReportConfig || {};
    var reportLanguage = 'id';
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

    function isEnglish() {
        return reportLanguage === 'en';
    }

    function text(id, fallback) {
        var copy = {
            id: {
                audienceAnalytics: 'Dashboard Analitik Penonton',
                allReports: 'Semua Laporan',
                generated: 'Dibuat',
                confidential: 'Sinemaku Pictures - Laporan Analitik Rahasia',
                page: 'Halaman',
                of: 'dari',
                summaryReport: 'Laporan Ringkasan Investor',
                executiveSummary: 'Ringkasan Eksekutif',
                reportScope: 'CAKUPAN LAPORAN',
                investmentHighlights: 'POIN UTAMA INVESTOR',
                methodology: 'Metodologi & Catatan Penting',
                totalAudience: 'Total Penonton',
                totalCities: 'Jumlah Kota',
                totalCinemas: 'Jumlah Bioskop',
                totalShows: 'Jumlah Show',
                grossBoxOffice: 'Gross Box Office',
                netBoxOffice: 'Net Box Office',
                producerProceeds: 'Estimasi Pendapatan Produser',
                detailRows: 'Baris Detail',
                film: 'Nama Film',
                period: 'Periode',
                cinemaCategory: 'Kategori Bioskop',
                all: 'Semua',
                audience: 'Penonton',
                cities: 'Kota',
                cinemas: 'Bioskop',
                show: 'Show',
                dashboardInsight: 'Insight Dashboard',
                chartInsight: 'Insight Grafik',
                category: 'Kategori',
                contribution: 'Kontribusi',
                noData: 'Tidak ada data untuk filter yang dipilih.',
                chartUnavailable: 'Grafik tidak tersedia.',
                summaryByCinemaCategory: 'Ringkasan Berdasarkan Kategori Bioskop',
                cinemaPerformanceRanking: 'Peringkat Performa Bioskop',
                total: 'TOTAL',
                finalTotal: 'Estimasi Pendapatan Produser',
                methodologyLines: [
                    'Laporan ini disusun untuk kebutuhan peninjauan investor dan menggunakan data pelaporan bioskop yang tersedia.',
                    'Gross Box Office adalah pendapatan tiket yang dilaporkan; pajak, pembagian pendapatan, dan royalty mengikuti asumsi konfigurasi laporan.',
                    'Estimasi Pendapatan Produser bukan laporan keuangan teraudit dan bergantung pada kelengkapan serta akurasi data sumber.'
                ]
            },
            en: {
                audienceAnalytics: 'Audience Analytics Dashboard',
                allReports: 'All Reports',
                generated: 'Generated',
                confidential: 'Sinemaku Pictures - Confidential Investor Report',
                page: 'Page',
                of: 'of',
                summaryReport: 'Investor Summary Report',
                executiveSummary: 'Executive Summary',
                reportScope: 'REPORT SCOPE',
                investmentHighlights: 'INVESTMENT HIGHLIGHTS',
                methodology: 'Methodology & Important Notes',
                totalAudience: 'Total Admissions',
                totalCities: 'Cities Covered',
                totalCinemas: 'Cinemas Covered',
                totalShows: 'Show Slots',
                grossBoxOffice: 'Gross Box Office',
                netBoxOffice: 'Net Box Office',
                producerProceeds: 'Estimated Producer Proceeds',
                detailRows: 'Detail Rows',
                film: 'Film',
                period: 'Reporting Period',
                cinemaCategory: 'Cinema Category',
                all: 'All',
                audience: 'Admissions',
                cities: 'Cities',
                cinemas: 'Cinemas',
                show: 'Show',
                dashboardInsight: 'Dashboard Insight',
                chartInsight: 'Chart Insight',
                category: 'Category',
                contribution: 'Contribution',
                noData: 'No data is available for the selected filters.',
                chartUnavailable: 'Chart is unavailable.',
                summaryByCinemaCategory: 'Summary by Cinema Category',
                cinemaPerformanceRanking: 'Cinema Performance Ranking',
                total: 'TOTAL',
                finalTotal: 'Estimated Producer Proceeds',
                methodologyLines: [
                    'This report is prepared for investor review using available theatrical reporting data.',
                    'Gross Box Office represents reported ticket revenue; tax, revenue sharing, and royalty follow the configured reporting assumptions.',
                    'Estimated Producer Proceeds are not audited financial statements and depend on the completeness and accuracy of source reporting.'
                ]
            }
        };
        return (copy[isEnglish() ? 'en' : 'id'][id] || fallback || id);
    }

    function reportText(key) {
        var copy = {
            dashboardAudiencePerformance: ['Dashboard Audience Performance', 'Audience Performance Dashboard'],
            dashboardSummary: ['Ringkasan Dashboard', 'Dashboard Summary'],
            dashboardSubtitle: ['Ringkasan performa penonton berdasarkan filter aktif.', 'Admissions performance overview for the selected filters.'],
            topCities: ['Top 20 Kota', 'Top 20 Cities'],
            topCitiesSubtitle: ['Kota dengan jumlah penonton tertinggi berdasarkan filter aktif.', 'Cities ranked by total admissions for the selected filters.'],
            admissionsByShow: ['Penonton per Show', 'Admissions by Show'],
            admissionsByShowSubtitle: ['Distribusi penonton berdasarkan urutan show.', 'Admissions distribution by show slot.'],
            cinemaNetworkMix: ['Komposisi Jaringan Bioskop', 'Cinema Network Mix'],
            cinemaNetworkMixSubtitle: ['Kontribusi penonton berdasarkan jaringan bioskop.', 'Admissions contribution by cinema network.'],
            topCinemas: ['Top 20 Bioskop', 'Top 20 Cinemas'],
            topCinemasSubtitle: ['Bioskop dengan jumlah penonton tertinggi.', 'Cinemas ranked by total admissions.'],
            underperformingCities: ['Kota dengan Performa Terendah', 'Underperforming Cities'],
            underperformingCitiesSubtitle: ['Kota dengan jumlah penonton terendah yang masih memiliki transaksi.', 'Cities with the lowest admissions while still recording transactions.'],
            underperformingCinemas: ['Bioskop dengan Performa Terendah', 'Underperforming Cinemas'],
            underperformingCinemasSubtitle: ['Bioskop dengan jumlah penonton terendah yang masih memiliki transaksi.', 'Cinemas with the lowest admissions while still recording transactions.'],
            chartSummary: ['Ringkasan Grafik', 'Chart Summary'],
            chartData: ['Data Grafik', 'Chart Data'],
            chartTable: ['Tabel', 'Table'],
            chartDetailSubtitle: ['Rincian angka yang membentuk grafik pada halaman sebelumnya.', 'Numerical detail supporting the chart on the preceding page.'],
            chartInsight: ['Insight Grafik', 'Chart Insight'],
            primaryValue: ['Nilai utama', 'Key metric'],
            totalChartData: ['Total data grafik', 'Total chart data'],
            admissions: ['penonton', 'admissions'],
            categories: ['kategori', 'categories'],
            rank: ['Peringkat', 'Rank'],
            city: ['Kota', 'City'],
            cinema: ['Bioskop', 'Cinema'],
            cinemaName: ['Nama Bioskop', 'Cinema Name'],
            cinemaNetwork: ['Jaringan Bioskop', 'Cinema Network'],
            cinemaPerformanceRanking: ['Peringkat Performa Bioskop', 'Cinema Performance Ranking'],
            contribution: ['Kontribusi', 'Contribution'],
            availableSeats: ['Kapasitas Tersedia', 'Available Seats'],
            occupancy: ['Okupansi', 'Occupancy'],
            effectiveTaxRate: ['Tarif Pajak Efektif', 'Effective Tax Rate'],
            revenueShare: ['Bagi Hasil Bioskop 50%', 'Cinema Revenue Share 50%'],
            royalty: ['Royalti 1,5%', 'Royalty 1.5%'],
            boxOfficeSummary: ['Ringkasan Performa Box Office', 'Box Office Performance Summary'],
            boxOfficeSubtitle: ['Jembatan pendapatan dari Gross Box Office hingga Estimasi Pendapatan Produser.', 'Revenue bridge from Gross Box Office to Estimated Producer Proceeds.'],
            revenueWaterfall: ['JEMBATAN PENDAPATAN', 'REVENUE BRIDGE'],
            lessTax: ['Dikurangi: Pajak', 'Less: Tax'],
            lessRevenueShare: ['Dikurangi: Bagi Hasil Bioskop', 'Less: Cinema Revenue Share'],
            lessRoyalty: ['Dikurangi: Royalti', 'Less: Royalty'],
            boxOfficeInsight: ['Insight Box Office', 'Box Office Insight'],
            financeInsight: ['Finance Insight', 'Financial Insight'],
            financeSubtitle: ['Ringkasan pasar teratas berdasarkan provinsi dan bioskop.', 'Leading market overview by province and cinema.'],
            province: ['Provinsi', 'Province'],
            managementInsight: ['Insight Manajemen', 'Management Insight'],
            trendAnalysis: ['Analisis Tren', 'Trend Analysis'],
            trendSubtitle: ['Pergerakan Gross Box Office, Estimasi Pendapatan Produser, dan penonton dari hari ke hari.', 'Daily movement of Gross Box Office, Estimated Producer Proceeds, and admissions.'],
            dailyTrendChart: ['Grafik Tren Harian', 'Daily Trend Chart'],
            trendInsight: ['Insight Tren', 'Trend Insight'],
            trendTable: ['Tabel Analisis Tren', 'Trend Analysis Table'],
            trendTableSubtitle: ['Rincian angka utama yang membentuk grafik tren harian.', 'Key daily figures supporting the trend chart.'],
            date: ['Tanggal', 'Date'],
            dailyPerformance: ['Laporan Performa Theatrical Harian', 'Daily Theatrical Performance Report'],
            totalAdmissions: ['Total Penonton', 'Total Admissions'],
            ticketPrice: ['Harga Tiket', 'Ticket Price'],
            tax: ['Pajak', 'Tax'],
            taxRate: ['Pajak %', 'Tax %'],
            noData: ['Tidak ada data untuk filter yang dipilih.', 'No data is available for the selected filters.'],
            noTrendData: ['Data belum cukup untuk membaca tren.', 'There is not enough data to interpret the trend.'],
            noFinanceNotes: ['Tidak ada catatan khusus.', 'No specific notes are available.'],
            methodologySubtitle: ['Definisi dan asumsi yang digunakan untuk membaca ringkasan investor ini.', 'Definitions and assumptions used to read this investor summary.'],
            generatedFrom: ['Laporan ini dibuat dari data pelaporan theatrical yang tersedia.', 'This report is generated from available theatrical reporting data.']
        };
        var value = copy[key] || [key, key];
        return value[isEnglish() ? 1 : 0];
    }

    function reportLines(key) {
        var lines = {
            methodology: {
                id: [
                    'Laporan ini disusun untuk kebutuhan peninjauan investor dan menggunakan data pelaporan bioskop yang tersedia.',
                    'Gross Box Office adalah pendapatan tiket yang dilaporkan; pajak, pembagian pendapatan, dan royalti mengikuti asumsi konfigurasi laporan.',
                    'Estimasi Pendapatan Produser bukan laporan keuangan teraudit dan bergantung pada kelengkapan serta akurasi data sumber.'
                ],
                en: [
                    'This report is prepared for investor review using available theatrical reporting data.',
                    'Gross Box Office represents reported ticket revenue; tax, revenue sharing, and royalty follow the configured reporting assumptions.',
                    'Estimated Producer Proceeds are not audited financial statements and depend on the completeness and accuracy of source reporting.'
                ]
            }
        };
        var value = lines[key] || { id: [], en: [] };
        return value[isEnglish() ? 'en' : 'id'];
    }

    function investorSentence(type, data) {
        data = data || {};
        if (type === 'primary') {
            return isEnglish()
                ? 'Key metric: ' + data.label + ' generated ' + reportNumber(data.value, 0) + ' admissions.'
                : 'Nilai utama: ' + data.label + ' menghasilkan ' + reportNumber(data.value, 0) + ' penonton.';
        }
        if (type === 'chartTotal') {
            return isEnglish()
                ? 'The chart represents ' + reportNumber(data.total, 0) + ' admissions across ' + reportNumber(data.count, 0) + ' ' + reportText('categories') + '.'
                : 'Total data grafik: ' + reportNumber(data.total, 0) + ' penonton dari ' + reportNumber(data.count, 0) + ' kategori.';
        }
        return '';
    }

    function reportNumber(value, decimals) {
        return numberValue(value).toLocaleString(isEnglish() ? 'en-US' : 'id-ID', {
            minimumFractionDigits: decimals || 0,
            maximumFractionDigits: decimals || 0
        });
    }

    function reportCurrency(value, decimals) {
        return 'IDR ' + reportNumber(value, typeof decimals === 'number' ? decimals : 0);
    }

    function reportPercent(value) {
        return reportNumber(value, 2) + '%';
    }

    function displayDate(value) {
        var match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) {
            return value || '-';
        }
        return isEnglish() ? match[2] + '/' + match[3] + '/' + match[1] : match[3] + '-' + match[2] + '-' + match[1];
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
            period: displayDate(filters.tgl_mulai) + (isEnglish() ? ' to ' : ' s.d. ') + displayDate(filters.tgl_akhir),
            category: selectedCategoryValue === filters.bioskop_kategori
                ? selectedText('#bioskop_kategori', text('all'))
                : text('all')
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
            title: isEnglish() ? 'Preparing Investor Summary Report' : 'Menyiapkan Summary Report',
            position: 'top-end',
            toast: true,
            width: 390,
            html:
                '<div style="color:#6b7280;font-size:12px;margin-bottom:12px">' +
                    (isEnglish() ? 'Collecting report data. Please keep this page open.' : 'Mengumpulkan seluruh data laporan. Mohon jangan menutup halaman.') +
                '</div>' +
                '<div style="height:10px;background:#ecebfa;border-radius:999px;overflow:hidden">' +
                    '<div id="summary-report-progress-bar" style="width:8%;height:100%;background:linear-gradient(90deg,#26225e,#716bd3);border-radius:999px;transition:width .3s ease"></div>' +
                '</div>' +
                '<div id="summary-report-progress-label" style="margin-top:9px;color:#374151;font-size:12px;font-weight:700">8% · ' + (isEnglish() ? 'Preparing report parameters...' : 'Menyiapkan parameter laporan...') + '</div>',
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

    function showReportNotice(title, message) {
        if (window.Swal) {
            return Swal.fire({
                type: 'warning',
                title: title,
                text: message,
                confirmButtonText: isEnglish() ? 'Close' : 'Tutup',
                confirmButtonColor: '#26225e'
            });
        }
        window.alert(message);
        return null;
    }

    function showDownloadError(message) {
        if (window.Swal) {
            Swal.fire({
                type: 'error',
                title: reportLanguage === 'en' ? 'Summary Report could not be generated' : 'Summary Report gagal dibuat',
                text: message || (reportLanguage === 'en' ? 'Please try again.' : 'Silakan coba kembali.'),
                confirmButtonText: reportLanguage === 'en' ? 'Close' : 'Tutup',
                confirmButtonColor: '#26225e'
            });
            return;
        }

        alert(message || 'Summary Report gagal dibuat. Silakan coba kembali.');
    }

    function chooseReportLanguage() {
        if (!window.Swal) {
            return Promise.resolve('id');
        }

        // SweetAlert2 yang dipakai project ini belum mendukung tombol sekunder.
        // Gunakan tombol HTML sendiri agar Indonesia/English selalu tampil dan
        // callback tetap berjalan pada versi SweetAlert2 lama maupun baru.
        return new Promise(function (resolve) {
            var settled = false;
            var settle = function (language) {
                if (settled) {
                    return;
                }
                settled = true;
                $(document).off('click.summaryReportLanguage');
                resolve(language);
            };

            var modal = Swal.fire({
                title: 'Download Investment Summary Report',
                html:
                    '<div style="color:#6b7280;font-size:13px;line-height:1.6;margin-bottom:18px">' +
                        'Pilih bahasa laporan yang akan digunakan di seluruh PDF.<br>' +
                        '<strong>English report tetap menggunakan mata uang IDR.</strong>' +
                    '</div>' +
                    '<div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">' +
                        '<button type="button" class="btn btn-outline-secondary" id="summary-report-language-id" style="min-width:160px">Bahasa Indonesia</button>' +
                        '<button type="button" class="btn btn-primary" id="summary-report-language-en" style="min-width:160px;background:#26225e;border-color:#26225e">English</button>' +
                    '</div>',
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Batal',
                cancelButtonColor: '#9ca3af',
                allowOutsideClick: true,
                allowEscapeKey: true
            });

            $(document)
                .off('click.summaryReportLanguage', '#summary-report-language-id, #summary-report-language-en')
                .on('click.summaryReportLanguage', '#summary-report-language-id, #summary-report-language-en', function () {
                    var language = this.id === 'summary-report-language-en' ? 'en' : 'id';
                    settle(language);
                    Swal.close();
                });

            // Cancel/outside-click resolves to null. Guarded `settle` prevents
            // duplicate resolution when a language button closes the modal.
            if (modal && typeof modal.then === 'function') {
                modal.then(function () {
                    settle(null);
                });
            }
        });
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

    function createReportChartImage(type, labels, values, datasetLabel) {
        if (!window.Chart || !labels || !labels.length) {
            return null;
        }

        var canvas = document.createElement('canvas');
        canvas.width = 1500;
        canvas.height = 650;
        var isDoughnut = type === 'doughnut';
        var isLine = type === 'line';
        var palette = ['#625bd6', '#047857', '#2563eb', '#ea580c', '#be123c', '#0891b2', '#7c3aed', '#65a30d'];
        var dataset = {
            label: datasetLabel || text('audience'),
            data: values.map(numberValue),
            borderWidth: isLine ? 4 : (isDoughnut ? 2 : 0),
            borderColor: isLine ? '#625bd6' : (isDoughnut ? '#ffffff' : palette[0]),
            backgroundColor: isDoughnut ? labels.map(function (_, index) { return palette[index % palette.length]; }) : (isLine ? 'rgba(98,91,214,0.15)' : '#625bd6'),
            pointRadius: isLine ? 6 : 0,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#625bd6',
            pointBorderWidth: isLine ? 3 : 0,
            tension: isLine ? 0.3 : 0,
            fill: isLine
        };
        var chart = new Chart(canvas.getContext('2d'), {
            type: type,
            data: { labels: labels, datasets: [dataset] },
            options: {
                responsive: false,
                animation: false,
                maintainAspectRatio: false,
                indexAxis: type === 'bar' ? 'y' : 'x',
                plugins: {
                    legend: {
                        display: isDoughnut || isLine,
                        position: 'bottom',
                        labels: { color: '#374151', usePointStyle: true, padding: 22, font: { size: 16 } }
                    }
                },
                scales: isDoughnut ? {} : {
                    x: { beginAtZero: true, ticks: { color: '#6b7280', font: { size: 14 } }, grid: { color: 'rgba(107,114,128,0.10)' } },
                    y: { beginAtZero: true, ticks: { color: '#374151', font: { size: 14 } }, grid: { color: 'rgba(107,114,128,0.08)' } }
                }
            }
        });
        var image = chart.toBase64Image();
        chart.destroy();
        return image;
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
                        label: text('producerProceeds'),
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
                        label: text('grossBoxOffice'),
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
                        label: text('audience'),
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
        var generatedAt = generatedDate.toLocaleString(isEnglish() ? 'en-US' : 'id-ID', {
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
            doc.text(text('audienceAnalytics'), marginX + 18, 18);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8.2);
            doc.setTextColor.apply(doc, colors.muted);
            doc.text(section || text('allReports'), pageW - marginX, 13, { align: 'right' });
            doc.text(text('generated') + ': ' + generatedAt, pageW - marginX, 18, { align: 'right' });
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
                doc.text(text('confidential'), marginX, pageH - 8);
                doc.text(text('page') + ' ' + page + ' ' + text('of') + ' ' + pages, pageW - marginX, pageH - 8, { align: 'right' });
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
                [text('film'), labels.film],
                [text('period'), labels.period],
                [text('cinemaCategory'), labels.category]
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
            if (title) {
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(8.5);
                doc.setTextColor.apply(doc, colors.text);
                doc.text(title, x + 4, y + 7, { maxWidth: width - 8 });
            }

            if (!imageData) {
                doc.setFont('helvetica', 'normal');
                doc.setTextColor.apply(doc, colors.muted);
                doc.text(text('chartUnavailable'), x + (width / 2), y + (height / 2), { align: 'center' });
                return;
            }

            var image = doc.getImageProperties(imageData);
            var topPadding = title ? 11 : 4;
            var availableH = height - topPadding - 4;
            var ratio = Math.min((width - 8) / image.width, availableH / image.height);
            var imageW = image.width * ratio;
            var imageH = image.height * ratio;
            doc.addImage(
                imageData,
                'PNG',
                x + ((width - imageW) / 2),
                y + topPadding + ((availableH - imageH) / 2),
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
                investorSentence('primary', { label: primaryLabel, value: primaryValue }),
                investorSentence('chartTotal', { total: total, count: labels.length })
            ];

            nextPage(options.title, options.subtitle, options.section || reportText('chartSummary'));
            imagePanel(options.image, options.chartTitle || '', marginX + 16, 48, usableW - 32, 110);
            insightBox(reportText('chartInsight'), insightLines, 164, 23);

            nextPage(
                reportText('chartTable') + ' ' + options.title,
                reportText('chartDetailSubtitle'),
                (options.section || reportText('chartSummary')) + ' - ' + reportText('chartData')
            );
            var rows = labels.map(function (label, index) {
                var value = numberValue(values[index]);
                return [
                    index + 1,
                    String(label || '-').toUpperCase(),
                    reportNumber(value, 0),
                    total ? reportPercent((value / total) * 100) : reportPercent(0)
                ];
            });
            var compactTable = rows.length > 18;
            doc.autoTable({
                startY: 48,
                margin: { left: marginX, right: marginX, bottom: 18 },
                head: [[reportText('rank'), options.dimension || text('category'), options.valueLabel || text('audience'), reportText('contribution')]],
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

        function addCompactChartAndTable(options) {
            var labels = options.labels || [];
            var values = options.values || [];
            var total = values.reduce(function (sum, value) {
                return sum + numberValue(value);
            }, 0);
            var highestValue = values.length ? Math.max.apply(null, values.map(numberValue)) : 0;
            var highestIndex = values.map(numberValue).indexOf(highestValue);
            var primaryLabel = labels[highestIndex] || '-';
            var rows = labels.map(function (label, index) {
                var value = numberValue(values[index]);
                return [
                    index + 1,
                    String(label || '-').toUpperCase(),
                    reportNumber(value, 0),
                    total ? reportPercent((value / total) * 100) : reportPercent(0)
                ];
            });
            var columnGap = 7;
            var chartWidth = 128;
            var tableX = marginX + chartWidth + columnGap;
            var tableWidth = usableW - chartWidth - columnGap;

            nextPage(options.title, options.subtitle, options.section || reportText('chartSummary'));
            imagePanel(options.image, '', marginX, 48, chartWidth, 111);

            doc.autoTable({
                startY: 48,
                margin: { left: tableX, right: marginX, bottom: 18 },
                tableWidth: tableWidth,
                head: [[reportText('rank'), options.dimension || text('category'), options.valueLabel || text('audience'), reportText('contribution')]],
                body: rows.length ? rows : [[{
                    content: reportText('noData'),
                    colSpan: 4,
                    styles: { halign: 'center', textColor: colors.muted }
                }]],
                theme: 'grid',
                pageBreak: 'avoid',
                rowPageBreak: 'avoid',
                styles: {
                    font: 'helvetica',
                    fontSize: 7.3,
                    cellPadding: 2,
                    textColor: colors.text,
                    valign: 'middle'
                },
                headStyles: {
                    fillColor: colors.brand,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold'
                },
                alternateRowStyles: { fillColor: colors.soft },
                columnStyles: {
                    0: { cellWidth: 14, halign: 'center' },
                    1: { cellWidth: 'auto' },
                    2: { cellWidth: 27, halign: 'right' },
                    3: { cellWidth: 25, halign: 'right' }
                }
            });

            insightBox(reportText('chartInsight'), [
                investorSentence('primary', { label: primaryLabel, value: highestValue }),
                investorSentence('chartTotal', { total: total, count: labels.length })
            ], 165, 23);
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
        var gap = 4;
        var cardW = (usableW - (gap * 3)) / 4;

        addHeader(text('executiveSummary'));
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(23);
        doc.setTextColor.apply(doc, colors.text);
        doc.text(text('summaryReport'), marginX, 60);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(11);
        doc.setTextColor.apply(doc, colors.muted);
        doc.text(isEnglish() ? 'Theatrical performance and investment overview' : 'Ringkasan performa theatrical dan overview investasi', marginX, 69);
        doc.setDrawColor.apply(doc, colors.accent);
        doc.setLineWidth(0.7);
        doc.line(marginX, 76, pageW - marginX, 76);
        doc.setFontSize(10);
        doc.setTextColor.apply(doc, colors.text);
        doc.text(text('film') + ': ' + String(labels.film || '-'), marginX, 91);
        doc.text(text('period') + ': ' + String(labels.period || '-'), marginX, 99);
        doc.text(text('cinemaCategory') + ': ' + String(labels.category || '-'), marginX, 107);
        metricCard(text('totalAudience'), reportNumber(metrics.audience || financeSummary.audience, 0), marginX, 122, cardW, colors.purple);
        metricCard(text('grossBoxOffice'), reportCurrency(financeSummary.gross, 0), marginX + cardW + gap, 122, cardW, colors.primary);
        metricCard(text('netBoxOffice'), reportCurrency(financeSummary.net, 0), marginX + ((cardW + gap) * 2), 122, cardW, colors.green);
        metricCard(text('producerProceeds'), reportCurrency(financeSummary.total_ph, 0), marginX + ((cardW + gap) * 3), 122, cardW, colors.purple);
        insightBox(text('investmentHighlights'), [
            (isEnglish() ? 'The report covers ' : 'Laporan mencakup ') + reportNumber(metrics.audience || financeSummary.audience, 0) + ' ' + (isEnglish() ? 'admissions across ' : 'penonton dari ') + reportNumber(metrics.cities || financeSummary.city_count, 0) + ' ' + (isEnglish() ? 'cities.' : 'kota.'),
            (isEnglish() ? 'Estimated Producer Proceeds: ' : 'Estimasi Pendapatan Produser: ') + reportCurrency(financeSummary.total_ph, 0) + '.',
            (isEnglish() ? 'This summary is prepared for investor review and is not an audited financial statement.' : 'Ringkasan ini disiapkan untuk peninjauan investor dan bukan laporan keuangan teraudit.')
        ], 153, 31);
        doc.addPage('a4', 'landscape');
        pageTitle(text('summaryReport'), isEnglish() ? 'Executive overview of the selected theatrical reporting period.' : 'Ringkasan eksekutif berdasarkan periode pelaporan theatrical yang dipilih.', text('executiveSummary'));
        filterBox(48);
        metricCard(text('totalAudience'), reportNumber(metrics.audience || financeSummary.audience, 0), marginX, 79, cardW, colors.purple);
        metricCard(text('totalCities'), reportNumber(metrics.cities || financeSummary.city_count, 0), marginX + cardW + gap, 79, cardW, colors.blue);
        metricCard(text('totalCinemas'), reportNumber(metrics.cinemas || financeSummary.cinema_count, 0), marginX + ((cardW + gap) * 2), 79, cardW, colors.green);
        metricCard(text('totalShows'), reportNumber(metrics.shows, 0), marginX + ((cardW + gap) * 3), 79, cardW, colors.orange);
        metricCard(text('grossBoxOffice'), reportCurrency(financeSummary.gross, 0), marginX, 104, cardW, colors.primary);
        metricCard(text('netBoxOffice'), reportCurrency(financeSummary.net, 0), marginX + cardW + gap, 104, cardW, colors.green);
        metricCard(text('producerProceeds'), reportCurrency(financeSummary.total_ph, 0), marginX + ((cardW + gap) * 2), 104, cardW, colors.purple);
        metricCard(text('detailRows'), reportNumber(detail.row_count || detailRows.length, 0), marginX + ((cardW + gap) * 3), 104, cardW, colors.accent);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9.5);
        doc.setTextColor.apply(doc, colors.text);
        doc.text(text('reportScope'), marginX, 140);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor.apply(doc, colors.muted);
        doc.text([
            isEnglish() ? '1. Audience Performance Dashboard' : '1. Dashboard Audience Performance',
            isEnglish() ? '2. Top 20 City Analysis' : '2. Grafik TOP 20 Kota',
            isEnglish() ? '3. Trend Analysis' : '3. Trend Analysis',
            isEnglish() ? '4. Box Office Performance Summary' : '4. Ringkasan Performa Box Office',
            isEnglish() ? '5. Daily Theatrical Performance Report' : '5. Laporan Performa Theatrical Harian',
            isEnglish() ? '6. Finance Insight' : '6. Finance Insight'
        ], marginX, 148);

        var topCities = dashboard.top_cities || [];
        var shows = dashboard.shows_over_time || [];
        var cinemaGroups = dashboard.viewers_by_cinema || [];
        var topCinemas = dashboard.top_cinemas || [];
        var underCities = dashboard.underperf_cities || [];
        var underCinemas = dashboard.underperf_cinemas || [];

        nextPage(reportText('dashboardAudiencePerformance'), reportText('dashboardSubtitle'), reportText('dashboardSummary'));
        metricCard(text('totalAudience'), reportNumber(metrics.audience, 0), marginX, 50, cardW, colors.purple);
        metricCard(text('totalCities'), reportNumber(metrics.cities, 0), marginX + cardW + gap, 50, cardW, colors.blue);
        metricCard(text('totalShows'), reportNumber(metrics.shows, 0), marginX + ((cardW + gap) * 2), 50, cardW, colors.orange);
        metricCard(text('totalCinemas'), reportNumber(metrics.cinemas, 0), marginX + ((cardW + gap) * 3), 50, cardW, colors.green);
        insightBox(text('dashboardInsight'), [
            isEnglish()
                ? 'The selected period covers ' + reportNumber(metrics.cities, 0) + ' cities and ' + reportNumber(metrics.cinemas, 0) + ' cinemas.'
                : 'Periode terpilih mencakup ' + reportNumber(metrics.cities, 0) + ' kota dan ' + reportNumber(metrics.cinemas, 0) + ' bioskop.',
            isEnglish()
                ? 'Total admissions reached ' + reportNumber(metrics.audience, 0) + ' across ' + reportNumber(metrics.shows, 0) + ' reported show slots.'
                : 'Total penonton mencapai ' + reportNumber(metrics.audience, 0) + ' dari ' + reportNumber(metrics.shows, 0) + ' urutan show yang dilaporkan.'
        ], 82, 25);

        addChartAndTable({
            title: reportText('topCities'),
            subtitle: reportText('topCitiesSubtitle'),
            section: reportText('dashboardSummary') + ' / ' + reportText('topCities'),
            image: createReportChartImage('bar', topCities.map(function (row) { return row.kota; }), topCities.map(function (row) { return row.jumlah; }), text('audience')),
            dimension: reportText('city'),
            labels: topCities.map(function (row) { return row.kota; }),
            values: topCities.map(function (row) { return row.jumlah; })
        });
        addCompactChartAndTable({
            title: reportText('admissionsByShow'),
            subtitle: reportText('admissionsByShowSubtitle'),
            section: reportText('dashboardSummary'),
            image: createReportChartImage('line', shows.map(function (row) { return row.show; }), shows.map(function (row) { return row.jumlah; }), text('audience')),
            dimension: reportText('show'),
            labels: shows.map(function (row) { return row.show; }),
            values: shows.map(function (row) { return row.jumlah; })
        });
        addCompactChartAndTable({
            title: reportText('cinemaNetworkMix'),
            subtitle: reportText('cinemaNetworkMixSubtitle'),
            section: reportText('dashboardSummary'),
            image: createReportChartImage('doughnut', cinemaGroups.map(function (row) { return row.bioskop; }), cinemaGroups.map(function (row) { return row.penonton; }), text('audience')),
            dimension: reportText('cinemaNetwork'),
            labels: cinemaGroups.map(function (row) { return row.bioskop; }),
            values: cinemaGroups.map(function (row) { return row.penonton; })
        });
        addChartAndTable({
            title: reportText('topCinemas'),
            subtitle: reportText('topCinemasSubtitle'),
            section: reportText('dashboardSummary'),
            image: createReportChartImage('bar', topCinemas.map(function (row) { return row.bioskop; }), topCinemas.map(function (row) { return row.penonton; }), text('audience')),
            dimension: reportText('cinemaName'),
            labels: topCinemas.map(function (row) { return row.bioskop; }),
            values: topCinemas.map(function (row) { return row.penonton; })
        });
        addChartAndTable({
            title: reportText('underperformingCities'),
            subtitle: reportText('underperformingCitiesSubtitle'),
            section: reportText('dashboardSummary'),
            image: createReportChartImage('bar', underCities.map(function (row) { return row.kota; }), underCities.map(function (row) { return row.penonton; }), text('audience')),
            dimension: reportText('city'),
            labels: underCities.map(function (row) { return row.kota; }),
            values: underCities.map(function (row) { return row.penonton; }),
            primaryLabel: underCities.length ? underCities[0].kota : '-',
            primaryValue: underCities.length ? underCities[0].penonton : 0,
            insights: underCities.length ? [
                isEnglish()
                    ? 'Lowest-performing city: ' + String(underCities[0].kota || '-').toUpperCase() + ' with ' + reportNumber(underCities[0].penonton, 0) + ' admissions.'
                    : 'Kota dengan performa terendah: ' + String(underCities[0].kota || '-').toUpperCase() + ' dengan ' + reportNumber(underCities[0].penonton, 0) + ' penonton.',
                isEnglish() ? 'Use this signal to review scheduling, promotion, and city coverage.' : 'Gunakan sinyal ini untuk mengevaluasi jadwal, promosi, dan coverage kota.'
            ] : [isEnglish() ? 'No underperforming city data is available for the selected filters.' : 'Belum ada data kota dengan performa terendah pada filter aktif.']
        });
        addChartAndTable({
            title: reportText('underperformingCinemas'),
            subtitle: reportText('underperformingCinemasSubtitle'),
            section: reportText('dashboardSummary'),
            image: createReportChartImage('bar', underCinemas.map(function (row) { return row.nama_bioskop; }), underCinemas.map(function (row) { return row.penonton; }), text('audience')),
            dimension: reportText('cinemaName'),
            labels: underCinemas.map(function (row) { return row.nama_bioskop; }),
            values: underCinemas.map(function (row) { return row.penonton; }),
            primaryLabel: underCinemas.length ? underCinemas[0].nama_bioskop : '-',
            primaryValue: underCinemas.length ? underCinemas[0].penonton : 0,
            insights: underCinemas.length ? [
                isEnglish()
                    ? 'Lowest-performing cinema: ' + String(underCinemas[0].nama_bioskop || '-').toUpperCase() + ' with ' + reportNumber(underCinemas[0].penonton, 0) + ' admissions.'
                    : 'Bioskop dengan performa terendah: ' + String(underCinemas[0].nama_bioskop || '-').toUpperCase() + ' dengan ' + reportNumber(underCinemas[0].penonton, 0) + ' penonton.',
                isEnglish() ? 'Review location performance and showtime effectiveness before scaling investment.' : 'Tinjau performa lokasi dan efektivitas jadwal tayang sebelum memperluas investasi.'
            ] : [isEnglish() ? 'No underperforming cinema data is available for the selected filters.' : 'Belum ada data bioskop dengan performa terendah pada filter aktif.']
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

            nextPage(reportText('boxOfficeSummary'), reportText('boxOfficeSubtitle'), reportText('boxOfficeSummary'));
            metricCard(text('grossBoxOffice'), reportCurrency(totals.gross, 0), marginX, 50, cardW, colors.primary);
            metricCard(reportText('tax'), reportCurrency(totals.tax, 0), marginX + cardW + gap, 50, cardW, colors.accent);
            metricCard(text('netBoxOffice'), reportCurrency(totals.net, 0), marginX + ((cardW + gap) * 2), 50, cardW, colors.green);
            metricCard(text('producerProceeds'), reportCurrency(totals.totalPh, 0), marginX + ((cardW + gap) * 3), 50, cardW, colors.purple);
            metricCard('ATP', reportCurrency(atp, 0), marginX, 76, cardW, colors.orange);
            metricCard(reportText('occupancy'), reportPercent(occupancy), marginX + cardW + gap, 76, cardW, colors.blue);
            metricCard(reportText('revenueShare'), reportCurrency(totals.share, 0), marginX + ((cardW + gap) * 2), 76, cardW, colors.primary);
            metricCard(reportText('royalty'), reportCurrency(totals.royalty, 0), marginX + ((cardW + gap) * 3), 76, cardW, colors.accent);

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9);
            doc.setTextColor.apply(doc, colors.text);
            doc.text(reportText('revenueWaterfall'), marginX, 105);

            var waterfall = [
                { label: text('grossBoxOffice'), value: totals.gross, color: [124, 58, 237] },
                { label: reportText('lessTax'), value: totals.tax, prefix: '-', color: [220, 38, 38] },
                { label: text('netBoxOffice'), value: totals.net, color: [2, 132, 199] },
                { label: reportText('lessRevenueShare'), value: totals.share, prefix: '-', color: [234, 88, 12] },
                { label: reportText('lessRoyalty'), value: totals.royalty, prefix: '-', color: [219, 39, 119] },
                { label: text('producerProceeds'), value: totals.totalPh, color: [5, 150, 105] }
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
                doc.text((step.prefix || '') + reportCurrency(step.value, 0), pageW - marginX, y + 4.7, { align: 'right' });
            });

            insightBox(reportText('boxOfficeInsight'), [
                isEnglish()
                    ? reportCurrency(totals.gross, 0) + ' Gross Box Office converts into ' + reportCurrency(totals.net, 0) + ' Net Box Office after tax.'
                    : reportCurrency(totals.gross, 0) + ' Gross Box Office menghasilkan ' + reportCurrency(totals.net, 0) + ' Net Box Office setelah pajak.',
                isEnglish()
                    ? 'After cinema revenue share and royalty, Estimated Producer Proceeds reach ' + reportCurrency(totals.totalPh, 0) + '.'
                    : 'Setelah bagi hasil bioskop dan royalti, Estimasi Pendapatan Produser mencapai ' + reportCurrency(totals.totalPh, 0) + '.'
            ], 162, 24);

            function addRekapTable(title, headers, rows, columnStyles, fontSize, totalRow) {
                var tableRows = rows.length ? rows.slice() : [[{
                    content: reportText('noData'),
                    colSpan: headers.length,
                    styles: { halign: 'center', textColor: colors.muted }
                }]];

                if (rows.length && totalRow) {
                    tableRows.push(totalRow);
                }

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
                text('summaryByCinemaCategory'),
                ['No', text('category'), text('audience'), isEnglish() ? 'Available Seats' : 'Kapasitas Tersedia', 'Occupancy', 'Gross', 'ATP', isEnglish() ? 'Effective Tax Rate' : 'Pajak Efektif', 'Net', isEnglish() ? 'Revenue Share 50%' : 'Share 50%', 'Royalty', text('finalTotal')],
                rekapSummaryRows.map(function (row, index) {
                    return [index + 1, row.kategori || '-', reportNumber(row.jumlah, 0), reportNumber(row.seats_available, 0), row.occupancy_rate || '0.00%', reportCurrency(row.gross, 0), reportCurrency(row.atp, 0), row.effective_tax_rate || '0.00%', reportCurrency(row.net, 0), reportCurrency(row.share, 0), row.royalty || '1.5%', reportCurrency(row.total, 0)];
                }),
                { 0: { cellWidth: 8 }, 1: { cellWidth: 24 }, 2: { cellWidth: 19 }, 3: { cellWidth: 24 }, 4: { cellWidth: 18 }, 5: { cellWidth: 28 }, 6: { cellWidth: 21 }, 7: { cellWidth: 21 }, 8: { cellWidth: 27 }, 9: { cellWidth: 26 }, 10: { cellWidth: 16 }, 11: { cellWidth: 28 } },
                5.8,
                [
                    { content: text('total'), colSpan: 2, styles: { halign: 'right', fontStyle: 'bold' } },
                    { content: reportNumber(totals.audience, 0), styles: { halign: 'right', fontStyle: 'bold' } },
                    { content: '', colSpan: 8 },
                    { content: reportCurrency(totals.totalPh, 0), styles: { halign: 'right', fontStyle: 'bold' } }
                ]
            );

            addRekapTable(
                reportText('cinemaPerformanceRanking'),
                [reportText('rank'), reportText('city'), reportText('cinemaName'), text('audience'), reportText('availableSeats'), reportText('occupancy'), text('grossBoxOffice'), 'ATP', text('netBoxOffice'), text('producerProceeds')],
                rekapPerformanceRows.map(function (row, index) {
                    return [index + 1, row.kota || '-', String(row.nama_bioskop || '-').toUpperCase(), reportNumber(row.jumlah, 0), reportNumber(row.seats_available, 0), row.occupancy_rate || '0.00%', reportCurrency(row.gross, 0), reportCurrency(row.atp, 0), reportCurrency(row.net, 0), reportCurrency(row.total_ph, 0)];
                }),
                { 0: { cellWidth: 10 }, 1: { cellWidth: 22 }, 2: { cellWidth: 45 }, 3: { cellWidth: 20 }, 4: { cellWidth: 25 }, 5: { cellWidth: 20 }, 6: { cellWidth: 30 }, 7: { cellWidth: 24 }, 8: { cellWidth: 30 }, 9: { cellWidth: 35 } },
                rekapPerformanceRows.length > 35 ? 5.5 : 6.3
            );

        }

        addBoxOfficeSection();

        function addFinanceSection() {
            nextPage(reportText('financeInsight'), reportText('financeSubtitle'), reportText('financeInsight'));
            var provinceData = finance.province_leaderboard || [];
            var cinemaData = finance.leaderboard || [];
            var provinceRows = provinceData.map(function (row, index) {
                return [index + 1, row.provinsi || '-', reportNumber(row.city_count, 0), reportNumber(row.cinema_count, 0), reportNumber(row.audience, 0), reportCurrency(row.gross, 0), reportCurrency(row.total_ph, 0)];
            });
            doc.autoTable({
                startY: 48,
                margin: { left: marginX, right: marginX, bottom: 18 },
                head: [[reportText('rank'), reportText('province'), text('cities'), text('cinemas'), text('audience'), text('grossBoxOffice'), text('producerProceeds')]],
                body: provinceRows.length ? provinceRows : [[{ content: reportText('noData'), colSpan: 7, styles: { halign: 'center' } }]],
                theme: 'grid',
                styles: { font: 'helvetica', fontSize: 7, cellPadding: 1.5, textColor: colors.text },
                headStyles: { fillColor: colors.brand, textColor: [255, 255, 255], fontStyle: 'bold' },
                alternateRowStyles: { fillColor: colors.soft }
            });
            var cinemaRows = cinemaData.map(function (row, index) {
                return [index + 1, String(row.nama_bioskop || '-').toUpperCase(), row.kota || '-', reportNumber(row.audience, 0), reportCurrency(row.gross, 0), reportCurrency(row.total_ph, 0)];
            });
            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 7,
                margin: { left: marginX, right: marginX, bottom: 18 },
                head: [[reportText('rank'), reportText('cinemaName'), reportText('city'), text('audience'), text('grossBoxOffice'), text('producerProceeds')]],
                body: cinemaRows.length ? cinemaRows : [[{ content: reportText('noData'), colSpan: 6, styles: { halign: 'center' } }]],
                theme: 'grid',
                styles: { font: 'helvetica', fontSize: 7, cellPadding: 1.5, textColor: colors.text },
                headStyles: { fillColor: colors.brand, textColor: [255, 255, 255], fontStyle: 'bold' },
                alternateRowStyles: { fillColor: colors.soft }
            });
            var financeNotes = [];
            if (provinceData.length) {
                financeNotes.push(isEnglish()
                    ? 'Leading province: ' + String(provinceData[0].provinsi || '-').toUpperCase() + ' with ' + reportNumber(provinceData[0].audience, 0) + ' admissions and ' + reportCurrency(provinceData[0].gross, 0) + ' Gross Box Office.'
                    : 'Provinsi teratas: ' + String(provinceData[0].provinsi || '-').toUpperCase() + ' dengan ' + reportNumber(provinceData[0].audience, 0) + ' penonton dan Gross Box Office ' + reportCurrency(provinceData[0].gross, 0) + '.');
            }
            if (cinemaData.length) {
                financeNotes.push(isEnglish()
                    ? 'Leading cinema: ' + String(cinemaData[0].nama_bioskop || '-').toUpperCase() + ' in ' + String(cinemaData[0].kota || '-') + '.'
                    : 'Bioskop teratas: ' + String(cinemaData[0].nama_bioskop || '-').toUpperCase() + ' di ' + String(cinemaData[0].kota || '-') + '.');
            }
            insightBox(reportText('managementInsight'), financeNotes.length ? financeNotes : [reportText('noFinanceNotes')], doc.lastAutoTable.finalY + 5, 32);
        }

        nextPage(reportText('trendAnalysis'), reportText('trendSubtitle'), reportText('trendAnalysis'));
        var trendImage = createTrendImage(trendDaily);
        imagePanel(trendImage, '', marginX + 16, 48, usableW - 32, 110);
        var trendNotes = [];
        if (trendSummary.period_change !== null && typeof trendSummary.period_change !== 'undefined') {
            trendNotes.push(isEnglish()
                ? 'Estimated Producer Proceeds moved ' + (numberValue(trendSummary.period_change) >= 0 ? 'up' : 'down') + ' by ' + reportPercent(Math.abs(numberValue(trendSummary.period_change))) + ' from the first reporting day to the last.'
                : 'Estimasi Pendapatan Produser bergerak ' + (numberValue(trendSummary.period_change) >= 0 ? 'naik' : 'turun') + ' ' + reportPercent(Math.abs(numberValue(trendSummary.period_change))) + ' dari hari pertama ke hari terakhir.');
        }
        if (trendSummary.best_day && trendSummary.best_day.tanggal) {
            trendNotes.push(isEnglish()
                ? 'Best day by Estimated Producer Proceeds: ' + displayDate(trendSummary.best_day.tanggal) + '.'
                : 'Hari terbaik berdasarkan Estimasi Pendapatan Produser: ' + displayDate(trendSummary.best_day.tanggal) + '.');
        }
        insightBox(reportText('trendInsight'), trendNotes.length ? trendNotes.slice(0, 2) : [reportText('noTrendData')], 164, 23);

        nextPage(reportText('trendTable'), reportText('trendTableSubtitle'), reportText('trendAnalysis') + ' - ' + reportText('chartData'));
        var trendRows = trendDaily.map(function (row) {
            return [
                displayDate(row.tanggal),
                reportCurrency(row.total_ph, 0),
                reportCurrency(row.gross, 0),
                reportNumber(row.audience, 0)
            ];
        });
        var trendCompact = trendRows.length > 26;
        doc.autoTable({
            startY: 48,
            margin: { left: marginX, right: marginX, bottom: 18 },
            head: [[reportText('date'), text('producerProceeds'), text('grossBoxOffice'), text('audience')]],
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
                reportCurrency(row.harga, 0),
                reportCurrency(row.gross, 0),
                reportPercent(row.pajak_persen),
                reportCurrency(row.pajak, 0),
                reportCurrency(row.net, 0),
                reportCurrency(row.share_ph, 0),
                reportCurrency(row.royalty, 0),
                reportCurrency(row.total_akhir, 0)
            ];
        });
        var detailTotals = detailRows.reduce(function (result, row) {
            result.audience += numberValue(row.Total);
            result.gross += numberValue(row.gross);
            result.tax += numberValue(row.pajak);
            result.net += numberValue(row.net);
            result.share += numberValue(row.share_ph);
            result.royalty += numberValue(row.royalty);
            result.proceeds += numberValue(row.total_akhir);
            return result;
        }, { audience: 0, gross: 0, tax: 0, net: 0, share: 0, royalty: 0, proceeds: 0 });
        if (pdfDetailRows.length) {
            pdfDetailRows.push([
                { content: text('total'), colSpan: 3, styles: { halign: 'right', fontStyle: 'bold' } },
                { content: reportNumber(detailTotals.audience, 0), styles: { halign: 'right', fontStyle: 'bold' } },
                '', '',
                { content: reportCurrency(detailTotals.gross, 0), styles: { halign: 'right', fontStyle: 'bold' } },
                '',
                { content: reportCurrency(detailTotals.tax, 0), styles: { halign: 'right', fontStyle: 'bold' } },
                { content: reportCurrency(detailTotals.net, 0), styles: { halign: 'right', fontStyle: 'bold' } },
                { content: reportCurrency(detailTotals.share, 0), styles: { halign: 'right', fontStyle: 'bold' } },
                { content: reportCurrency(detailTotals.royalty, 0), styles: { halign: 'right', fontStyle: 'bold' } },
                { content: reportCurrency(detailTotals.proceeds, 0), styles: { halign: 'right', fontStyle: 'bold' } }
            ]);
        }
        doc.autoTable({
            startY: 43,
            margin: { top: 43, left: marginX, right: marginX, bottom: 18 },
            head: [[
                reportText('date'), reportText('city'), reportText('cinemaName'), reportText('totalAdmissions'), reportText('occupancy'), reportText('ticketPrice'), text('grossBoxOffice'),
                reportText('taxRate'), reportText('tax'), text('netBoxOffice'), reportText('revenueShare'), reportText('royalty'), text('producerProceeds')
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
                addHeader(reportText('dailyPerformance'));
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(10.5);
                doc.setTextColor.apply(doc, colors.text);
                doc.text(reportText('dailyPerformance'), marginX, 35);
            }
        });

        // Finance Insight ditempatkan paling akhir setelah laporan detail.
        addFinanceSection();

        nextPage(text('methodology'), reportText('methodologySubtitle'), text('methodology'));
        insightBox(text('methodology'), reportLines('methodology'), 52, 43);

        addFooter();
        var safeFilm = String(labels.film || (isEnglish() ? 'all-films' : 'semua-film')).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        var filenamePrefix = isEnglish() ? 'investment-summary' : 'laporan-ringkasan';
        doc.save(filenamePrefix + '-' + (safeFilm || (isEnglish() ? 'all-films' : 'semua-film')) + '-' + reportLanguage + '.pdf');
    }

    function startSummaryReportDownload() {
        var $button = $('#download-all-reports');
        var originalHtml = $button.html();
        var filters = activeFilters();
        var copy = isEnglish() ? {
            preparing: 'Preparing report...',
            finance: 'Financial Insights',
            trend: 'Trend Analysis',
            detail: 'Report Detail',
            rekap: 'Box Office Summary',
            ranking: 'Cinema Performance Ranking',
            loaded: ' loaded.',
            assembling: 'Building PDF pages and tables...',
            completed: 'Investment Summary Report complete. Download is starting...'
        } : {
            preparing: 'Menyiapkan laporan...',
            finance: 'Insight Keuangan',
            trend: 'Analisis Tren',
            detail: 'Detail Laporan',
            rekap: 'Ringkasan Box Office',
            ranking: 'Peringkat Performa Bioskop',
            loaded: ' selesai dimuat.',
            assembling: 'Menyusun halaman dan tabel PDF...',
            completed: 'Laporan Ringkasan Investor selesai. Download dimulai...'
        };

        if (!filters.nama_film || !filters.tgl_mulai || !filters.tgl_akhir) {
            showReportNotice(
                isEnglish() ? 'Report filters are incomplete' : 'Filter laporan belum lengkap',
                isEnglish() ? 'Select a film and reporting period before downloading the report.' : 'Pilih film dan periode laporan sebelum mengunduh report.'
            );
            return;
        }

        if (!window.sinemakuLatestDashboardPayload) {
            showReportNotice(
                isEnglish() ? 'Dashboard data is not ready' : 'Data Dashboard belum siap',
                isEnglish() ? 'Please wait for the dashboard to finish loading, then try again.' : 'Tunggu Dashboard selesai dimuat, lalu coba kembali.'
            );
            return;
        }

        $button.prop('disabled', true).html('<i class="fal fa-spinner fa-spin mr-1"></i> ' + copy.preparing);
        openDownloadProgress();

        var commonParams = $.extend({}, filters);
        var completedSources = 0;
        var markSourceComplete = function (label) {
            completedSources += 1;
            updateDownloadProgress(10 + (completedSources * 14), label + copy.loaded);
        };
        var financeRequest = fetchReport(config.financeUrl, commonParams, copy.finance).then(function (response) {
            markSourceComplete(copy.finance);
            return response;
        });
        var trendRequest = fetchReport(config.trendUrl, commonParams, copy.trend).then(function (response) {
            markSourceComplete(copy.trend);
            return response;
        });
        var detailRequest = fetchReport(
            config.detailUrl,
            $.extend({}, commonParams, { format: 'json' }),
            copy.detail
        ).then(function (response) {
            markSourceComplete(copy.detail);
            return response;
        });
        var rekapSummaryRequest = fetchReport(config.rekapSummaryUrl, commonParams, copy.rekap).then(function (response) {
            markSourceComplete(copy.rekap);
            return response;
        });
        var rekapPerformanceRequest = fetchReport(config.rekapPerformanceUrl, commonParams, copy.ranking).then(function (response) {
            markSourceComplete(copy.ranking);
            return response;
        });
        Promise.all([
            financeRequest,
            trendRequest,
            detailRequest,
            rekapSummaryRequest,
            rekapPerformanceRequest
        ])
            .then(function (responses) {
                updateDownloadProgress(82, copy.assembling);
                buildCombinedPdf({
                    dashboard: window.sinemakuLatestDashboardPayload,
                    finance: responses[0],
                    trend: responses[1],
                    detail: responses[2],
                    rekap: {
                        summary: responses[3],
                        performance: responses[4]
                    }
                }, filters, filterLabels(filters));
                updateDownloadProgress(100, copy.completed);

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
                showDownloadError(error.message || (isEnglish() ? 'Failed to generate the Summary Report PDF. Please try again.' : 'Gagal membuat Summary Report PDF. Silakan coba kembali.'));
            })
            .then(function () {
                $button.prop('disabled', false).html(originalHtml);
            });
    }

    $(document).on('click', '#download-all-reports', function () {
        chooseReportLanguage().then(function (language) {
            if (!language) {
                return;
            }
            reportLanguage = language;
            startSummaryReportDownload();
        });
    });
})(window, window.jQuery);
