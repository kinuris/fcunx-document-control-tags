<template>
    <div>
        <NcDashboardWidget :items="items" :show-more-url="showMoreUrl" :show-more-text="title"
            :loading="state === 'loading'">
            <template #empty-content>
                <NcRichText text="Hello" />
                <NcEmptyContent title="Document Control Tags">
                    <template #icon>
                        <FolderOutlineIcon />
                    </template>
                </NcEmptyContent>
            </template>
        </NcDashboardWidget>
        <NcRichText text="Hello" />
    </div>
</template>

<script>
import FolderOutlineIcon from 'vue-material-design-icons/FolderOutline.vue'

import NcDashboardWidget from '@nextcloud/vue/dist/Components/NcDashboardWidget.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcRichText from '@nextcloud/vue/dist/Components/NcRichText.js'

import { loadState } from '@nextcloud/initial-state'

export default {
    name: 'TagCounterWidget',
    components: {
        NcDashboardWidget,
        NcEmptyContent,
        FolderOutlineIcon,
        NcRichText,
    },
    props: {
        title: {
            type: String,
            required: true,
        },
    },
    data: () => {
        const items = loadState('documentcontroltags', 'dashboard');
        console.log(items);

        return {
            tagItems: items,
            title: 'Document Control Tags',
            state: 'ok',
        }
    },
    computed: {
        items() {
            return this.tagItems.map((tag) => ({
                id: tag.id,
                mainText: tag.title,
                subText: tag.subtitle + (tag.subtitle == 1 ? ' Document' : ' Documents'),
                avatarUrl: tag.iconUrl,
            }));
        }
    },
    watch: {},
    beforeDestroy: () => { },
    beforeMount: () => { },
    mounted: () => { },
    methods: {},
}
</script>