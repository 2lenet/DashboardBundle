import Chart from 'chart.js/auto';

global.Chart = Chart;

window.addEventListener('load', function () {
    setTimeout(() => {
        print();
    }, 500);
});
