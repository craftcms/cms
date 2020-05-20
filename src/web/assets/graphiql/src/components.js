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
        return elem(GraphiQL.Menu, {
            class: 'menu',
            label: this.props.selectedSchema.name,
            title: "Select a GraphQL schema"
        }, this.props.menuItems)
    }
}

class Root extends React.Component {
    constructor(props) {
        super(props);
        this.graphiql = React.createRef();
    }

    handleClickPrettifyButton(event) {
        this.graphiql.current.handlePrettifyQuery();
    }


    handleClickHistoryButton(event) {
        this.graphiql.current.handleToggleHistory();
    }

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

        let children = [
            elem(GraphiQL.Button, {
                onClick: this.handleClickPrettifyButton.bind(this),
                label: "Prettify",
                title: "Prettify query"
            }),
            elem(GraphiQL.Button, {
                onClick: this.handleClickHistoryButton.bind(this),
                label: "History",
                title: "Toggle history"
            }),
            elem(SchemaSelector, {menuItems: menuItems, selectedSchema: this.props.selectedSchema}),
        ];

        children.push

        // empty toolbar to remove default toolbar buttons
        toolBar = elem(GraphiQL.Toolbar, {}, children);

        return elem(GraphiQL, {
            fetcher: this.props.fetcher,
            schema: undefined,
            query: parameters.query,
            variables: parameters.variables,
            operationName: parameters.operationName,
            onEditQuery: onEditQuery,
            onEditVariables: onEditVariables,
            onEditOperationName: onEditOperationName,
            ref: this.graphiql
        }, toolBar, logoElement);
    }
}
