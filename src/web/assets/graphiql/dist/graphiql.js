
var header = document.querySelector('#header');

if (header) {
    header.parentNode.removeChild(header);
}

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

// called when tokens are prepared
function initGraphiQl() {
    // Defines a GraphQL fetcher using the fetch API.
    function graphQLFetcher(graphQLParams) {
        return fetch(getEndpoint(), {
            method: 'post',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + selectedToken.token,
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
        return elem(GraphiQL.MenuItem, {label: props.name, title: props.name, onSelect: function() { setToken(props.uid) }})
    }

    function Display(props) {
        var text = props.text;

        return elem('span', {id: 'token-info'}, text);
    }

    function Root() {
        var logoElement = React.createElement(GraphiQL.Logo, {}, "Explore the GraphQL API")

        var menuItems = [];

        for (tokenName in gqlTokens) {
            var tokenUid = gqlTokens[tokenName];

            menuItems.push(elem(Item, Object.assign({}, {name: tokenName, uid: tokenUid})));
        }

        var toolBar = React.createElement(GraphiQL.Toolbar, {},
            elem(GraphiQL.Menu, {label: "Select token", title: "Select GQL token to use"}, menuItems),
            elem(Display, { text: 'Using the “' + selectedToken.name + '” token' })
        );

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

function setToken(uid) {
    var pattern = /tokenUid=[a-z0-9-]+/i;
    if (location.href.match(pattern)) {
        location.href = location.href.replace(pattern, 'tokenUid=' + uid);
    } else {
        if (location.href.indexOf('?') !== -1) {
            location.href += '&tokenUid=' + uid;
        } else {
            location.href += '?tokenUid=' + uid;
        }
    }
}

function getEndpoint() {
    return $('#graphiql').data('endpoint');
}
