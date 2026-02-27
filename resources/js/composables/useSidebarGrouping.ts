import { onMounted, ref } from 'vue';
import type { Ref } from 'vue';

export type SidebarGroupingView = 'operational' | 'contractual';

export type UseSidebarGroupingReturn = {
    groupingView: Ref<SidebarGroupingView>;
    updateGroupingView: (value: SidebarGroupingView) => void;
};

const STORAGE_KEY = 'sidebar:grouping-view';
const groupingView = ref<SidebarGroupingView>('operational');

export function useSidebarGrouping(): UseSidebarGroupingReturn {
    onMounted(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const stored = window.localStorage.getItem(STORAGE_KEY);
        if (stored === 'operational' || stored === 'contractual') {
            groupingView.value = stored;
        }
    });

    function updateGroupingView(value: SidebarGroupingView): void {
        groupingView.value = value;

        if (typeof window === 'undefined') {
            return;
        }

        window.localStorage.setItem(STORAGE_KEY, value);
    }

    return { groupingView, updateGroupingView };
}
