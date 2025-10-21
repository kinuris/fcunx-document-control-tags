<template>
    <div class="scrollable-container">
        <div class="today-stats">
            <div class="stat-item">
                <span class="stat-label">Archived Today</span>
                <span class="stat-count">{{ archivedTodayCount }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Uploaded Today</span>
                <span class="stat-count">{{ uploadedTodayCount }}</span>
            </div>
        </div>

        <NcDashboardWidget :items="items" :show-more-url="showMoreUrl" :show-more-text="title"
            :loading="state === 'loading'">
            <template #empty-content>
                <div class="chart-container">
                    <canvas ref="chartCanvas"></canvas>
                </div>
                <NcEmptyContent title="Document Control Tags">
                    <template #icon>
                        <FolderOutlineIcon />
                    </template>
                </NcEmptyContent>
            </template>
        </NcDashboardWidget>

        <div class="chart-container">
            <canvas ref="mainChartCanvas"></canvas>
        </div>
    </div>
</template>

<script>
import FolderOutlineIcon from 'vue-material-design-icons/FolderOutline.vue'

import NcDashboardWidget from '@nextcloud/vue/dist/Components/NcDashboardWidget.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'

import { loadState } from '@nextcloud/initial-state'
import { Chart, registerables } from 'chart.js'

// Register Chart.js components
Chart.register(...registerables)

export default {
    name: 'TagCounterWidget',
    components: {
        NcDashboardWidget,
        NcEmptyContent,
        FolderOutlineIcon,
    },
    props: {
        title: {
            type: String,
            required: true,
        },
    },
    data: () => {
        const items = loadState('documentcontroltags', 'dashboard');
        const archivedCount = loadState('documentcontroltags', 'archived');
        const uploadedCount = loadState('documentcontroltags', 'uploaded');

        return {
            tagItems: items,
            title: 'Document Control Tags',
            state: 'ok',
            chart: null,
            mainChart: null,
            archivedCount: archivedCount,
            uploadedCount: uploadedCount,
        }
    },
    computed: {
        items() {
            return this.tagItems.map((tag) => ({
                id: tag.id,
                mainText: tag.title,
                subText: tag.subtitle + (tag.subtitle == 1 ? ' Document' : ' Documents'),
                avatarUrl: tag.iconUrl,
                targetUrl: tag.link,
            }));
        },
        graphInput() {
            return this.tagItems.map((tag) => ({
                fullLabel: tag.title,
                label: tag.title.split('').filter(char => char >= 'A' && char <= 'Z').join('.'),
                value: tag.subtitle,
            }));
        },
        archivedTodayCount() {
            return this.archivedCount;
        },
        uploadedTodayCount() {
            return this.uploadedCount;
        }
    },
    watch: {
        graphInput: {
            handler(newData) {
                this.updateChart(newData)
            },
            deep: true,
        },
    },
    beforeDestroy() {
        if (this.chart) {
            this.chart.destroy()
        }
        if (this.mainChart) {
            this.mainChart.destroy()
        }
    },
    mounted() {
        this.$nextTick(() => {
            this.createChart()
            this.createMainChart()
        })
    },
    methods: {
        createChart() {
            if (this.$refs.chartCanvas) {
                const ctx = this.$refs.chartCanvas.getContext('2d')
                this.chart = new Chart(ctx, this.getChartConfig())
            }
        },
        createMainChart() {
            if (this.$refs.mainChartCanvas) {
                const ctx = this.$refs.mainChartCanvas.getContext('2d')
                this.mainChart = new Chart(ctx, this.getChartConfig())
            }
        },
        getChartConfig() {
            return {
                type: 'bar',
                data: {
                    labels: this.graphInput.map(item => item.label),
                    datasets: [{
                        label: 'Document Count',
                        data: this.graphInput.map(item => item.value),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(255, 205, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 159, 64, 0.6)',
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 205, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)',
                        ],
                        borderWidth: 1,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        title: {
                            display: true,
                            text: 'Document Control Tags Distribution',
                        },
                        tooltip: {
                            callbacks: {
                                title: (toolTipItems) => {
                                    const customLabels = ['Approved Documents', 'Rejected Documents', 'Requires Approval'];
                                    const index = toolTipItems[0].dataIndex

                                    return customLabels[index]
                                }
                            }
                        }
                    },
                },
            }
        },
        updateChart(newData) {
            if (this.chart) {
                this.chart.data.labels = newData.map(item => item.label)
                this.chart.data.datasets[0].data = newData.map(item => item.value)
                this.chart.update()
            }
            if (this.mainChart) {
                this.mainChart.data.labels = newData.map(item => item.label)
                this.mainChart.data.datasets[0].data = newData.map(item => item.value)
                this.mainChart.update()
            }
        },
    },
}
</script>

<style scoped>
.scrollable-container {
    height: 100%;
    max-height: 428px;
    overflow-y: auto;
    padding: 4px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.chart-container {
    width: 100%;
    min-height: 264px;
    padding: 16px;
    background-color: var(--color-main-background);
    border-radius: var(--border-radius-large);
    border: 1px solid var(--color-border);
}

.chart-container canvas {
    max-width: 100%;
    height: 100%;
}

.today-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    padding: 12px;
    background-color: var(--color-main-background);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-large);
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-align: center;
}

.stat-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-text-maxcontrast);
}

.stat-count {
    font-size: 24px;
    font-weight: 700;
    color: var(--color-primary-element);
    line-height: 1;
}
</style>