<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { reactive, watch } from 'vue';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();

// ✅ Only mark active if EXACT match
const isExactActive = (href?: string) => {
    if (!href) return false;
    return page.url.split('?')[0] === href; // strip query params
};

// ✅ Parent active if it or one of its children matches
const hasActiveChild = (items?: NavItem[]) => {
    if (!items) return false;
    return items.some(item => isExactActive(item.href));
};

const isParentActive = (item: NavItem) => {
    if (isExactActive(item.href)) return true;
    return hasActiveChild(item.items);
};

// ✅ reactive items to track collapsibles
const reactiveItems = reactive(
    props.items.map(item => ({
        ...item,
        isOpen: hasActiveChild(item.items),
    }))
);

// Watch for route changes → update open/active state
watch(() => page.url, () => {
    reactiveItems.forEach(item => {
        if (item.items) {
            item.isOpen = hasActiveChild(item.items);
        }
    });
});
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in reactiveItems" :key="item.title">
                <!-- Normal menu -->
                <SidebarMenuButton
                    v-if="!item.items"
                    as-child
                    :tooltip="item.title"
                    :is-active="isExactActive(item.href)"
                >
                    <Link :href="item.href || '#'">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>

                <!-- Collapsible with children -->
                <Collapsible
                    v-else
                    :default-open="hasActiveChild(item.items)"
                    class="group/collapsible"
                    v-model:open="item.isOpen"
                >
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton
                            :tooltip="item.title"
                            :is-active="isParentActive(item)"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <ChevronRight
                                class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                            />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem
                                v-for="subItem in item.items"
                                :key="subItem.title"
                            >
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="isExactActive(subItem.href)"
                                >
                                    <Link :href="subItem.href || '#'">
                                        <component :is="subItem.icon" />
                                        <span>{{ subItem.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </Collapsible>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
