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
    isRoot?: boolean;
}>();

const page = usePage();

// Check if exact URL matches
const isExactActive = (href?: string) => {
    if (!href || href === '/') return false;
    return page.url.split('?')[0] === href;
};

// Check if any child (recursively) is active
const hasActiveChild = (items?: NavItem[]): boolean => {
    if (!items) return false;
    return items.some(item => {
        if (isExactActive(item.href)) return true;
        if (item.items) return hasActiveChild(item.items);
        return false;
    });
};

// Check if parent should be marked active
const isParentActive = (item: NavItem) => {
    if (isExactActive(item.href)) return true;
    return hasActiveChild(item.items);
};

// Process items recursively to add isOpen state
const processItems = (items: NavItem[]): any[] => {
    return items.map(item => ({
        ...item,
        isOpen: hasActiveChild(item.items),
        items: item.items ? processItems(item.items) : undefined,
    }));
};

// Create reactive items with open state
const reactiveItems = reactive(processItems(props.items));

// Watch for route changes and update open state
const updateOpenStates = (items: any[]) => {
    items.forEach(item => {
        if (item.items) {
            item.isOpen = hasActiveChild(item.items);
            updateOpenStates(item.items);
        }
    });
};

watch(() => page.url, () => {
    updateOpenStates(reactiveItems);
});
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel v-if="isRoot">Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in reactiveItems" :key="item.title">
                <!-- Normal menu item without children -->
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

                <!-- Collapsible menu item with children -->
                <Collapsible
                    v-else
                    v-model:open="item.isOpen"
                    class="group/collapsible"
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
                            <template v-for="subItem in item.items" :key="subItem.title">
                                <!-- Nested submenu with children (Level 3) -->
                                <SidebarMenuSubItem v-if="subItem.items">
                                    <Collapsible
                                        v-model:open="subItem.isOpen"
                                        class="group/nested-collapsible"
                                    >
                                        <CollapsibleTrigger as-child>
                                            <SidebarMenuSubButton
                                                :is-active="isParentActive(subItem)"
                                            >
                                                <component :is="subItem.icon" v-if="subItem.icon" />
                                                <span>{{ subItem.title }}</span>
                                                <ChevronRight
                                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/nested-collapsible:rotate-90"
                                                />
                                            </SidebarMenuSubButton>
                                        </CollapsibleTrigger>

                                        <CollapsibleContent>
                                            <SidebarMenuSub>
                                                <SidebarMenuSubItem
                                                    v-for="nestedItem in subItem.items"
                                                    :key="nestedItem.title"
                                                >
                                                    <!-- Check if nested item has more children (Level 4+) -->
                                                    <template v-if="nestedItem.items">
                                                        <Collapsible
                                                            v-model:open="nestedItem.isOpen"
                                                            class="group/deep-collapsible"
                                                        >
                                                            <CollapsibleTrigger as-child>
                                                                <SidebarMenuSubButton>
                                                                    <component :is="nestedItem.icon" v-if="nestedItem.icon" />
                                                                    <span>{{ nestedItem.title }}</span>
                                                                    <ChevronRight
                                                                        class="ml-auto transition-transform duration-200 group-data-[state=open]/deep-collapsible:rotate-90"
                                                                    />
                                                                </SidebarMenuSubButton>
                                                            </CollapsibleTrigger>
                                                            <CollapsibleContent>
                                                                <!-- Recursive component for deeper levels -->
                                                                <NavMain :items="nestedItem.items" />
                                                            </CollapsibleContent>
                                                        </Collapsible>
                                                    </template>
                                                    
                                                    <!-- Regular nested item (leaf node) -->
                                                    <SidebarMenuSubButton
                                                        v-else
                                                        as-child
                                                        :is-active="isExactActive(nestedItem.href)"
                                                    >
                                                        <Link :href="nestedItem.href || '#'">
                                                            <component :is="nestedItem.icon" v-if="nestedItem.icon" />
                                                            <span>{{ nestedItem.title }}</span>
                                                        </Link>
                                                    </SidebarMenuSubButton>
                                                </SidebarMenuSubItem>
                                            </SidebarMenuSub>
                                        </CollapsibleContent>
                                    </Collapsible>
                                </SidebarMenuSubItem>

                                <!-- Regular submenu item (leaf node) -->
                                <SidebarMenuSubItem v-else>
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="isExactActive(subItem.href)"
                                    >
                                        <Link :href="subItem.href || '#'">
                                            <component :is="subItem.icon" v-if="subItem.icon" />
                                            <span>{{ subItem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </template>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </Collapsible>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>