<template>
    <div class="pluginstore-modal-flex">
        <template v-if="!!$slots['body']">
            <slot name="body"></slot>
        </template>
        <template v-else>
            <header v-if="!!$slots['header']" class="header">
                <slot name="header"></slot>
            </header>
            <div class="pluginstore-modal-main">
                <div class="pluginstore-modal-content">
                    <slot name="main"></slot>
                </div>
            </div>
        </template>
    </div>
</template>
