/**
 * Инициализация графиков для логов Nginx
 */

document.addEventListener('DOMContentLoaded', function () {

    // Получаем данные из data атрибутов
    const requestsChartElement = document.getElementById('requestsChart');
    const browserChartElement = document.getElementById('browserChart');

    if (!requestsChartElement || !browserChartElement) {
        console.warn('Что-то пошло не так');
        return;
    }

    // Получаем JSON данные
    const datesJson = requestsChartElement.getAttribute('data-dates');
    const countsJson = requestsChartElement.getAttribute('data-counts');
    const browserDatesJson = browserChartElement.getAttribute('data-dates');
    const browserDataJson = browserChartElement.getAttribute('data-browsers-json');

    // Парсим JSON
    const dates = datesJson ? JSON.parse(datesJson) : [];
    const counts = countsJson ? JSON.parse(countsJson) : [];
    const browserDates = browserDatesJson ? JSON.parse(browserDatesJson) : [];
    const browserData = browserDataJson ? JSON.parse(browserDataJson) : [];

    // Инициализируем график запросов
    if (dates.length > 0 && counts.length > 0) {
        new Chart(requestsChartElement.getContext('2d'), {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Число запросов',
                    data: counts,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    fill: true,
                    tension: 0.7
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Инициализируем график браузеров
    if (browserDates.length > 0 && browserData.length > 0) {
        const datasets = browserData.map(function (browser) {
            return {
                label: browser.name,
                data: browser.data,
                borderColor: browser.borderColor,
                backgroundColor: browser.backgroundColor,
                fill: false,
                tension: 0.4
            };
        });

        new Chart(browserChartElement.getContext('2d'), {
            type: 'line',
            data: {
                labels: browserDates,
                datasets: datasets
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Доля (%)'
                        },
                        ticks: {
                            callback: function (value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }
});
