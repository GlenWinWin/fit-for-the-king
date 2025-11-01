// Analytics Page JavaScript
class AnalyticsPage {
    constructor() {
        this.currentRange = 7;
        this.weightUnit = 'kg';
        this.charts = {};
        this.data = {};

        this.init();
    }

    init() {
        this.loadData();
        this.setupEventListeners();
        this.setupCharts();
    }

    setupEventListeners() {
        // Time range selector
        document.querySelectorAll('.time-range-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.time-range-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentRange = parseInt(e.target.dataset.range);
                this.loadData();
            });
        });

        // Weight unit toggle
        document.getElementById('weight-unit-toggle').addEventListener('click', () => {
            this.toggleWeightUnit();
        });

        // Theme toggle
        document.getElementById('theme-toggle').addEventListener('click', () => {
            this.toggleTheme();
        });
    }

    setupCharts() {
        // Initialize chart containers
        this.chartContainers = {
            weight: document.getElementById('weightChart'),
            steps: document.getElementById('stepsChart'),
            completion: document.getElementById('completionChart')
        };
    }

    async loadData() {
        this.showLoadingState();

        try {
            const response = await fetch(`api.php?api=dashboard&limit=${this.currentRange}`);
            const result = await response.json();

            if (result.success) {
                this.data = result.data;
                this.updateAllCharts();
                this.updateOverviewCards();
                this.updateStats();
            } else {
                console.error('Error loading analytics data');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    updateAllCharts() {
        this.updateWeightChart();
        this.updateStepsChart();
        this.updateDevotionCalendar();
        this.updateCompletionChart();
        this.updateProgressSummary();
    }

    updateWeightChart() {
        const ctx = this.chartContainers.weight.getContext('2d');
        const weightData = this.processWeightData(this.data.weight_history);

        if (this.charts.weight) {
            this.charts.weight.destroy();
        }

        this.charts.weight = new Chart(ctx, {
            type: 'line',
            data: {
                labels: weightData.labels,
                datasets: [{
                    label: 'Weight',
                    data: weightData.values,
                    borderColor: 'var(--primary-color)',
                    backgroundColor: 'rgba(74, 107, 255, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'var(--primary-color)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return `Weight: ${context.parsed.y} ${this.weightUnit}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'var(--border-color)'
                        },
                        ticks: {
                            color: 'var(--text-muted)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'var(--border-color)'
                        },
                        ticks: {
                            color: 'var(--text-muted)'
                        }
                    }
                }
            }
        });
    }

    updateStepsChart() {
        const ctx = this.chartContainers.steps.getContext('2d');
        const stepsData = this.processStepsData(this.data.steps_history);

        if (this.charts.steps) {
            this.charts.steps.destroy();
        }

        this.charts.steps = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: stepsData.labels,
                datasets: [{
                    label: 'Steps',
                    data: stepsData.values,
                    backgroundColor: 'var(--success-color)',
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'var(--border-color)'
                        },
                        ticks: {
                            color: 'var(--text-muted)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'var(--text-muted)'
                        }
                    }
                }
            }
        });
    }

    updateCompletionChart() {
        const ctx = this.chartContainers.completion.getContext('2d');
        const completionRate = this.calculateCompletionRate();

        if (this.charts.completion) {
            this.charts.completion.destroy();
        }

        this.charts.completion = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Remaining'],
                datasets: [{
                    data: [completionRate, 100 - completionRate],
                    backgroundColor: [
                        'var(--primary-color)',
                        'var(--bg-secondary)'
                    ],
                    borderWidth: 0,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    updateDevotionCalendar() {
        const calendar = document.getElementById('devotion-calendar');
        const daysInRange = Math.min(this.currentRange, 35);

        calendar.innerHTML = '';
        calendar.style.gridTemplateColumns = 'repeat(7, 1fr)';

        for (let i = 0; i < daysInRange; i++) {
            const day = document.createElement('div');
            day.className = 'calendar-day' + (Math.random() > 0.4 ? ' completed' : '');
            day.textContent = i + 1;
            day.title = `Day ${i + 1}: ${day.classList.contains('completed') ? 'Completed' : 'Missed'}`;
            calendar.appendChild(day);
        }
    }

    updateProgressSummary() {
        const fitnessProgress = this.calculateFitnessProgress();
        const spiritualProgress = this.data.stats.devotion_streak / 30 * 100;
        const consistencyProgress = this.calculateConsistency();

        document.getElementById('fitness-progress').style.width = `${fitnessProgress}%`;
        document.getElementById('fitness-value').textContent = `${Math.round(fitnessProgress)}%`;

        document.getElementById('spiritual-progress').style.width = `${spiritualProgress}%`;
        document.getElementById('spiritual-value').textContent = `${Math.round(spiritualProgress)}%`;

        document.getElementById('consistency-progress').style.width = `${consistencyProgress}%`;
        document.getElementById('consistency-value').textContent = `${Math.round(consistencyProgress)}%`;
    }

    updateOverviewCards() {
        // Weight overview
        const weightData = this.processWeightData(this.data.weight_history);
        if (weightData.values.length > 0) {
            const currentWeight = weightData.values[weightData.values.length - 1];
            const previousWeight = weightData.values.length > 1 ? weightData.values[0] : currentWeight;
            const change = currentWeight - previousWeight;

            document.getElementById('overview-weight').textContent = `${currentWeight} ${this.weightUnit}`;
            document.getElementById('overview-weight-change').textContent =
                `${change > 0 ? '+' : ''}${change.toFixed(1)} ${this.weightUnit}`;
            document.getElementById('overview-weight-change').className =
                `overview-change ${change < 0 ? 'positive' : change > 0 ? 'negative' : 'neutral'}`;
        }

        // Steps overview
        const stepsData = this.processStepsData(this.data.steps_history);
        if (stepsData.values.length > 0) {
            const currentSteps = stepsData.values[stepsData.values.length - 1];
            const previousSteps = stepsData.values.length > 1 ? stepsData.values[0] : currentSteps;
            const change = ((currentSteps - previousSteps) / previousSteps * 100).toFixed(1);

            document.getElementById('overview-steps').textContent = currentSteps.toLocaleString();
            document.getElementById('overview-steps-change').textContent =
                `${change > 0 ? '+' : ''}${change}%`;
            document.getElementById('overview-steps-change').className =
                `overview-change ${change > 0 ? 'positive' : change < 0 ? 'negative' : 'neutral'}`;
        }

        // Completion rate
        const completionRate = this.calculateCompletionRate();
        document.getElementById('overview-completion').textContent = `${Math.round(completionRate)}%`;
    }

    updateStats() {
        const weightData = this.processWeightData(this.data.weight_history);
        const stepsData = this.processStepsData(this.data.steps_history);

        // Weight stats
        if (weightData.values.length > 0) {
            const startWeight = weightData.values[0];
            const currentWeight = weightData.values[weightData.values.length - 1];
            const totalChange = currentWeight - startWeight;
            const goalWeight = startWeight * 0.95; // 5% weight loss goal

            document.getElementById('start-weight').textContent = `${startWeight.toFixed(1)} ${this.weightUnit}`;
            document.getElementById('current-weight').textContent = `${currentWeight.toFixed(1)} ${this.weightUnit}`;
            document.getElementById('total-weight-change').textContent =
                `${totalChange > 0 ? '+' : ''}${totalChange.toFixed(1)} ${this.weightUnit}`;
            document.getElementById('goal-weight').textContent = `${goalWeight.toFixed(1)} ${this.weightUnit}`;
        }

        // Steps stats
        if (stepsData.values.length > 0) {
            const totalSteps = stepsData.values.reduce((a, b) => a + b, 0);
            const avgSteps = totalSteps / stepsData.values.length;
            const goalDays = stepsData.values.filter(steps => steps >= 8000).length;

            document.getElementById('today-steps').textContent = stepsData.values[stepsData.values.length - 1].toLocaleString();
            document.getElementById('avg-steps').textContent = Math.round(avgSteps).toLocaleString();
            document.getElementById('goal-days').textContent = `${goalDays}/${stepsData.values.length}`;
            document.getElementById('total-steps').textContent = totalSteps.toLocaleString();
        }

        // Devotion stats
        const devotionCompletion = this.calculateDevotionCompletion();
        document.getElementById('devotion-completion').textContent = `${devotionCompletion}%`;
        document.getElementById('devotion-total').textContent = Math.floor(this.currentRange * (devotionCompletion / 100));

        // Completion stats
        const completionRate = this.calculateCompletionRate();
        const perfectDays = Math.floor(this.currentRange * (completionRate / 100));
        document.getElementById('perfect-days').textContent = perfectDays;
        document.getElementById('completion-rate').textContent = `${Math.round(completionRate)}%`;
    }

    processWeightData(weightHistory) {
        const sortedData = weightHistory.sort((a, b) => new Date(a.entry_date) - new Date(b.entry_date));
        const recentData = sortedData.slice(-this.currentRange);

        return {
            labels: recentData.map(entry => {
                const date = new Date(entry.entry_date);
                return this.formatDateLabel(date, this.currentRange);
            }),
            values: recentData.map(entry => parseFloat(entry.weight_value))
        };
    }

    processStepsData(stepsHistory) {
        const sortedData = stepsHistory.sort((a, b) => new Date(a.entry_date) - new Date(b.entry_date));
        const recentData = sortedData.slice(-this.currentRange);

        return {
            labels: recentData.map(entry => {
                const date = new Date(entry.entry_date);
                return this.formatDateLabel(date, this.currentRange);
            }),
            values: recentData.map(entry => parseInt(entry.steps_count))
        };
    }

    formatDateLabel(date, range) {
        if (range === 365) {
            return date.toLocaleDateString('en-US', { month: 'short' });
        } else if (range === 90) {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        } else {
            return date.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric' });
        }
    }

    calculateCompletionRate() {
        // Simulate completion rate based on available data
        const hasWeightData = this.data.weight_history && this.data.weight_history.length > 0;
        const hasStepsData = this.data.steps_history && this.data.steps_history.length > 0;
        const hasDevotion = this.data.stats.devotion_streak > 0;

        let completion = 0;
        if (hasWeightData) completion += 30;
        if (hasStepsData) completion += 30;
        if (hasDevotion) completion += 40;

        return Math.min(100, completion);
    }

    calculateDevotionCompletion() {
        const streak = this.data.stats.devotion_streak || 0;
        return Math.min(100, (streak / this.currentRange) * 100);
    }

    calculateFitnessProgress() {
        const stepsProgress = this.calculateStepsProgress();
        const weightProgress = this.calculateWeightProgress();
        return (stepsProgress + weightProgress) / 2;
    }

    calculateStepsProgress() {
        const stepsData = this.processStepsData(this.data.steps_history);
        if (stepsData.values.length === 0) return 0;

        const goalDays = stepsData.values.filter(steps => steps >= 8000).length;
        return (goalDays / stepsData.values.length) * 100;
    }

    calculateWeightProgress() {
        const weightData = this.processWeightData(this.data.weight_history);
        if (weightData.values.length < 2) return 50;

        const startWeight = weightData.values[0];
        const currentWeight = weightData.values[weightData.values.length - 1];
        const targetLoss = startWeight * 0.05; // 5% weight loss target
        const actualLoss = startWeight - currentWeight;

        return Math.min(100, (actualLoss / targetLoss) * 100);
    }

    calculateConsistency() {
        const weightConsistency = this.data.weight_history.length / this.currentRange * 100;
        const stepsConsistency = this.data.steps_history.length / this.currentRange * 100;
        const devotionConsistency = this.calculateDevotionCompletion();

        return (weightConsistency + stepsConsistency + devotionConsistency) / 3;
    }

    toggleWeightUnit() {
        this.weightUnit = this.weightUnit === 'kg' ? 'lbs' : 'kg';
        document.getElementById('weight-unit-toggle').textContent = this.weightUnit;

        // Convert weight data if needed
        if (this.data.weight_history && this.data.weight_history.length > 0) {
            const conversionFactor = this.weightUnit === 'lbs' ? 2.20462 : 1 / 2.20462;
            this.data.weight_history = this.data.weight_history.map(entry => ({
                ...entry,
                weight_value: entry.weight_value * conversionFactor,
                weight_unit: this.weightUnit
            }));
        }

        this.updateWeightChart();
        this.updateOverviewCards();
        this.updateStats();
    }

    toggleTheme() {
        const currentTheme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_theme&theme=${newTheme}`
        }).then(() => {
            document.body.classList.toggle('dark-mode');
            document.body.classList.toggle('light-mode');

            const icon = document.querySelector('#theme-toggle i');
            icon.className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';

            // Update charts for new theme
            this.updateAllCharts();
        });
    }

    showLoadingState() {
        const containers = document.querySelectorAll('.chart-container, .completion-chart');
        containers.forEach(container => {
            if (!container.querySelector('.loading')) {
                const loading = document.createElement('div');
                loading.className = 'loading';
                loading.textContent = 'Loading...';
                container.appendChild(loading);
            }
        });
    }
}

// Initialize analytics page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AnalyticsPage();
});