class Item extends React.Component {
    render() {
        return elem(GraphiQL.MenuItem, {
            label: this.props.name,
            title: this.props.name,
            onSelect: function() {
                setSchema(this.props.uid)
            }.bind(this)
        })
    }
}

class ToolBar extends React.Component {
    render() {
        return elem(GraphiQL.Toolbar, {class: 'menu'}, elem(GraphiQL.Menu, {
            label: this.props.selectedSchema.name,
            title: "Select a GraphQL schema"
        }, this.props.menuItems))
    }
}

class Root extends React.Component {
    render() {
        var logoElement = React.createElement(GraphiQL.Logo, {}, "Explore the GraphQL API")

        var menuItems = [];
        var toolBar;

        let schemaName = '';

        for (schemaName in this.props.gqlSchemas) {
            var schemaUid = gqlSchemas[schemaName];
            if (schemaName !== selectedSchema.name) {
                menuItems.push(elem(Item, Object.assign({}, {name: schemaName, uid: schemaUid})));
            } else {
                menuItems.push(elem('li', {class: 'selected-schema'}, schemaName));
            }
        }

        if (menuItems.length) {
            toolBar = elem(GraphiQL.Toolbar, {}, elem(ToolBar, {menuItems: menuItems, selectedSchema: this.props.selectedSchema}));
        } else {
            // empty toolbar to remove default toolbar buttons
            toolBar = elem(GraphiQL.Toolbar, {}, elem('div', {}, ''));
        }

        return elem(GraphiQL, {
            fetcher: this.props.fetcher,
            schema: undefined,
            query: parameters.query,
            variables: parameters.variables,
            operationName: parameters.operationName,
            onEditQuery: onEditQuery,
            onEditVariables: onEditVariables,
            onEditOperationName: onEditOperationName
        }, toolBar, logoElement);
    }
}
