// Parse the search string to get url parameters.
var search = window.location.search;
var parameters = {};
search.substr(1).split('&').forEach(function (entry) {
    var eq = entry.indexOf('=');
    if (eq >= 0) {
        parameters[decodeURIComponent(entry.slice(0, eq))] =
            decodeURIComponent(entry.slice(eq + 1));
    }
});

// if variables was provided, try to format it.
if (parameters.variables) {
    try {
        parameters.variables = JSON.stringify(JSON.parse(parameters.variables), null, 2);
    } catch (e) {
        // Do nothing, we want to display the invalid JSON as a string, rather
        // than present an error.
    }
}

// When the query and variables string is edited, update the URL bar so
// that it can be easily shared
function onEditQuery(newQuery) {
    parameters.query = newQuery;
    updateURL();
}

function onEditVariables(newVariables) {
    parameters.variables = newVariables;
    updateURL();
}

function onEditOperationName(newOperationName) {
    parameters.operationName = newOperationName;
    updateURL();
}

function updateURL() {
    var newSearch = '?' + Object.keys(parameters).filter(function (key) {
        return Boolean(parameters[key]);
    }).map(function (key) {
        return encodeURIComponent(key) + '=' + encodeURIComponent(parameters[key]);
    }).join('&');
    history.replaceState(null, null, newSearch);
}

var elem = React.createElement;

// called when schemas are prepared
function initGraphiQl() {
    // Defines a GraphQL fetcher using the fetch API.
    function graphQLFetcher(graphQLParams) {
        return fetch(getEndpoint(), {
            method: 'post',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Craft-Gql-Schema': selectedSchema.schema,
            },
            body: JSON.stringify(graphQLParams),
            credentials: 'include',
        }).then(function (response) {
            return response.text();
        }).then(function (responseBody) {
            try {
                return JSON.parse(responseBody);
            } catch (error) {
                return responseBody;
            }
        });
    }

    function Item (props) {
        return elem(GraphiQL.MenuItem, {label: props.name, title: props.name, onSelect: function() { setSchema(props.uid) }})
    }

    function Display(props) {
        var text = props.text;

        return elem('span', {id: 'schema-info'}, text);
    }

    function Root() {
        var logoElement = React.createElement(GraphiQL.Logo, {}, "Explore the GraphQL API")
        var menuItems = [];
        var toolBar;

        for (schemaName in gqlSchemas) {
            var schemaUid = gqlSchemas[schemaName];
            if (schemaName !== selectedSchema.name) {
                menuItems.push(elem(Item, Object.assign({}, {name: schemaName, uid: schemaUid})));
            } else {
                menuItems.push(elem('li', {class: 'selected-schema'}, schemaName));
            }
        }

        if (menuItems.length) {
            toolBar = React.createElement(GraphiQL.Toolbar, {class: 'menu'}, elem(GraphiQL.Menu, {
                    label: selectedSchema.name,
                    title: "Select a GraphQL schema"
                }, menuItems)
            );
        } else {
            // empty toolbar to remove default toolbar buttons
            toolBar = React.createElement(GraphiQL.Toolbar, {}, elem('div', {}, ''));
        }

        return elem(GraphiQL, {
            fetcher: graphQLFetcher,
            schema: undefined,
            query: parameters.query,
            variables: parameters.variables,
            operationName: parameters.operationName,
            onEditQuery: onEditQuery,
            onEditVariables: onEditVariables,
            onEditOperationName: onEditOperationName
        }, toolBar, logoElement);
    }

    ReactDOM.render(elem(Root), document.getElementById('graphiql'));
}

function setSchema(uid) {
    var pattern = /schemaUid=[a-z0-9-]+/i;
    if (location.href.match(pattern)) {
        location.href = location.href.replace(pattern, 'schemaUid=' + uid);
    } else {
        if (location.href.indexOf('?') !== -1) {
            location.href += '&schemaUid=' + uid;
        } else {
            location.href += '?schemaUid=' + uid;
        }
    }
}

function getEndpoint() {
    return $('#graphiql').data('endpoint');
}
