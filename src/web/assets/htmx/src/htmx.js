htmx.defineExtension('craft-cp', {
    onEvent: function(name, evt) {
        switch (name) {
            case 'htmx:configRequest':
                this.configureRequest(evt);
                break;
            case 'htmx:load':
                this.onLoad(evt);
                break;
        }
    },

    configureRequest: function(evt) {
        // Add the standard Craft headers
        Object.assign(evt.detail.headers, Craft._actionHeaders());
    },

    onLoad: function(evt) {
        if (evt.detail.elt === document.body) {
            return;
        }

        const allHeadHtml = evt.detail.elt.querySelectorAll("template.hx-head-html");
        const allBodyHtml = evt.detail.elt.querySelectorAll("template.hx-body-html");

        for (let i = 0; i < allHeadHtml.length; i++) {
            const headHtml = allHeadHtml[i].innerHTML;
            if (headHtml) {
                Craft.appendHeadHtml(headHtml);
            }
        }

        for (let i = 0; i < allBodyHtml.length; i++) {
            const bodyHtml = allBodyHtml[i].innerHTML;
            if (bodyHtml) {
                Craft.appendBodyHtml(bodyHtml);
            }
        }

        Craft.initUiElements(evt.detail.elt);
    }
});

htmx.defineExtension('craft-condition', {
    onEvent: function(name, evt) {
        switch (name) {
            case 'htmx:configRequest':
                this.configureRequest(evt);
                break;
        }
    },

    configureRequest: function(evt) {
        let $conditionContainer = $(evt.detail.target).children('.condition-main');
        if (!$conditionContainer.length) {
            $conditionContainer = $(evt.detail.target).closest('.condition-main');
        }
        const config = $conditionContainer.data('condition-config');
        if (config && config.name) {
            const vals = evt.detail.elt.getAttribute('hx-vals') || evt.detail.elt.getAttribute('data-hx-vals');
            const valNames = vals ? Object.keys(JSON.parse(vals)) : [];
            evt.detail.parameters = Object.fromEntries(
                Object.entries(evt.detail.parameters).filter(([n]) => valNames.includes(n) || n.indexOf(config.name) === 0)
            );
        }
        evt.detail.parameters.config = JSON.stringify(config || {});
    },
});

if (typeof Craft !== 'undefined') {
    Object.assign(htmx.config, {
        indicatorClass: 'spinner',
        requestClass: 'loading',
    });
}
