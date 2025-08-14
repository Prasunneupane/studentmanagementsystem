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
import { computed, reactive, watch, onMounted } from 'vue';

const props = defineProps<{
    items: NavItem[];
}>();

// Create reactive items with open state
const reactiveItems = reactive(
    props.items.map(item => ({
        ...item,
        isOpen: hasActiveChild(item.items)
    }))
);

// Watch for route changes to update open states
watch(() => page.url, () => {
    reactiveItems.forEach(item => {
        if (item.items) {
            item.isOpen = hasActiveChild(item.items);
        }
    });
});

const page = usePage();

const isActive = (href: string) => {
    // Exact match for current URL
    return page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?');
};

const hasActiveChild = (items?: NavItem[]) => {
    if (!items) return false;
    return items.some(item => item.href && isActive(item.href));
};

const isParentActive = (item: NavItem) => {
    if (item.href && isActive(item.href)) return true;
    return hasActiveChild(item.items);
};
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in reactiveItems" :key="item.title">
                <!-- Regular menu item without sub-items -->
                <SidebarMenuButton
                    v-if="!item.items"
                    as-child
                    :tooltip="item.title"
                    :is-active="item.href ? isActive(item.href) : false"
                >
                    <Link :href="item.href || '#'">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>

                <!-- Collapsible menu item with sub-items -->
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
                            <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="subItem.href ? isActive(subItem.href) : false"
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