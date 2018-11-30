import Vue from 'vue'
import CraftComponents from '@benjamindavid/craftcomponents'

Object.keys(CraftComponents).forEach(name => {
    Vue.component(name, CraftComponents[name])
})


