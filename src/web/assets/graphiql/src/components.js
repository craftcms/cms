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

class SchemaSelector extends React.Component {
    render() {
        let e =  elem(GraphiQL.Menu, {
            className: 'menu',
            label: this.props.selectedSchema.name,
            title: "Select a GraphQL schema"
        }, this.props.menuItems)

        return e;
    }
}

class CraftGraphiQL extends React.Component {
    _makeSchemaSelector(gqlSchemas, selectedSchema) {
        let menuItems = [];
        let schemaName = '';

        for (schemaName in gqlSchemas) {
            var schemaUid = gqlSchemas[schemaName];
            if (schemaName !== selectedSchema.name) {
                menuItems.push(elem(Item, Object.assign({}, {name: schemaName, uid: schemaUid, key: schemaUid})));
            } else {
                menuItems.push(elem('li', {className: 'selected-schema', key: schemaUid}, schemaName));
            }
        }

        return elem(SchemaSelector, {menuItems: menuItems, selectedSchema: selectedSchema, key: 'schemaSelector'});
    }

    render() {
        return elem(GraphiQLWithExtensions.GraphiQLWithExtensions, {
            fetcher: this.props.fetcher,
            schema: undefined,
            query: parameters.query,
            variables: parameters.variables,
            operationName: parameters.operationName,
            onEditQuery: onEditQuery,
            onEditVariables: onEditVariables,
            onEditOperationName: onEditOperationName,
            logoMessage: "Explore the GraphQL API",
            additionalControls: [this._makeSchemaSelector(this.props.gqlSchemas, this.props.selectedSchema)]
        });
    }
}
