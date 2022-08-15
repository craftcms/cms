import {buildClientSchema, getIntrospectionQuery} from 'graphql';
import React from 'react';
import GraphiQL from 'graphiql';
import GraphiQLExplorer from 'graphiql-explorer';

var elem = React.createElement;

// URL updater functions

// Parse the search string to get url parameters.
var search = window.location.search;
var parameters = {};

search
  .substring(1)
  .split('&')
  .forEach(function (entry) {
    var eq = entry.indexOf('=');
    if (eq >= 0) {
      parameters[decodeURIComponent(entry.slice(0, eq))] = decodeURIComponent(
        entry.slice(eq + 1)
      );
    }
  });

// if variables was provided, try to format it.
if (parameters.variables) {
  try {
    parameters.variables = JSON.stringify(
      JSON.parse(parameters.variables),
      null,
      2
    );
  } catch (e) {
    // Do nothing, we want to display the invalid JSON as a string, rather
    // than present an error.
  }
}

// Keep track of changing parameters.
function onEditQuery(newQuery) {
  parameters.query = newQuery;
}

function onEditVariables(newVariables) {
  parameters.variables = newVariables;
}

function getShareableURL() {
  const prefix = location.href.split('?')[0];
  return (
    prefix +
    '?' +
    Object.keys(parameters)
      .filter(function (key) {
        return Boolean(parameters[key]);
      })
      .map(function (key) {
        return (
          encodeURIComponent(key) + '=' + encodeURIComponent(parameters[key])
        );
      })
      .join('&')
  );
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

// Actual React Components
class Item extends React.Component {
  render() {
    return elem(GraphiQL.MenuItem, {
      label: this.props.name,
      title: this.props.name,
      onSelect: () => {
        setSchema(this.props.uid);
      },
    });
  }
}

class SchemaSelector extends React.Component {
  render() {
    let e = elem(
      GraphiQL.Menu,
      {
        className: 'menu',
        label: this.props.selectedSchema.name,
        title: 'Select a GraphQL schema',
      },
      this.props.menuItems
    );

    return e;
  }
}

export class CraftGraphiQL extends React.Component {
  constructor(props) {
    super(props);
    // Set up references
    this.graphiql = React.createRef();
    this.explorer = React.createRef();

    const params = new URLSearchParams(window.location.search);

    // And initial state.
    this.state = {
      schema: null,
      explorerIsOpen: false,
      query: params.get('query') ?? undefined,
      loadingSchema: true,
    };
  }

  // On everything ready, make explorer update its schema, too.
  componentDidMount() {
    // Make sure we're aware of the query.
    this.state.query = this.graphiql.current.state.query;
    if (this.state.loadingSchema) {
      this.graphiql.current.setState({isWaitingForResponse: true});
    }

    parameters.query = this.graphiql.current.getQueryEditor().options.value;
    parameters.variables =
      this.graphiql.current.getVariableEditor().options.value;

    this.props
      .fetcher({
        query: getIntrospectionQuery(),
      })
      .then((result) => {
        const editor = this.graphiql.current.getQueryEditor();
        this.graphiql.current.setState({isWaitingForResponse: false});
        this.setState({
          loadingSchema: false,
          schema: buildClientSchema(result.data),
        });
      });
  }

  // Event handlers
  handleEditQuery(query) {
    // On query change, save it to state, so everyone knows about it.
    this.setState({query});
    onEditQuery(query);
  }

  handleClickPrettifyButton(event) {
    this.graphiql.current.handlePrettifyQuery();
  }

  handleClickHistoryButton(event) {
    this.graphiql.current.handleToggleHistory();
  }

  handleClickExplorerButton(event) {
    // Toggle Explorer via the state
    this.setState({
      explorerIsOpen: !this.state.explorerIsOpen,
    });
  }

  handleClickShare(event) {
    window.Craft.ui.createCopyTextPrompt({
      label: Craft.t('app', 'Share query'),
      value: getShareableURL(),
    });
  }

  // Create the schema dropdown selector
  _makeSchemaSelector(gqlSchemas, selectedSchema) {
    let menuItems = [];
    let schemaName = '';

    for (schemaName in gqlSchemas) {
      var schemaUid = gqlSchemas[schemaName];
      if (schemaName !== selectedSchema.name) {
        menuItems.push(
          elem(
            Item,
            Object.assign(
              {},
              {name: schemaName, uid: schemaUid, key: schemaUid}
            )
          )
        );
      } else {
        menuItems.push(
          elem('li', {className: 'selected-schema', key: schemaUid}, schemaName)
        );
      }
    }

    return elem(SchemaSelector, {
      menuItems: menuItems,
      selectedSchema: selectedSchema,
      key: 'schemaSelector',
    });
  }

  render() {
    let logoElement = React.createElement(
      GraphiQL.Logo,
      {},
      Craft.t('app', 'Explore the GraphQL API')
    );

    // Set up the toolbar.
    let toolbarElements = [
      elem(GraphiQL.Button, {
        onClick: this.handleClickPrettifyButton.bind(this),
        label: Craft.t('app', 'Prettify'),
        title: Craft.t('app', 'Prettify query'),
        key: 'prettify',
      }),
      elem(GraphiQL.Button, {
        onClick: this.handleClickHistoryButton.bind(this),
        label: Craft.t('app', 'History'),
        title: Craft.t('app', 'Toggle history'),
        key: 'history',
      }),
      this._makeSchemaSelector(
        this.props.gqlSchemas,
        this.props.selectedSchema
      ),
      elem(GraphiQL.Button, {
        onClick: this.handleClickExplorerButton.bind(this),
        label: Craft.t('app', 'Explorer'),
        title: Craft.t('app', 'Toggle explorer'),
        key: 'explore',
      }),
      elem(GraphiQL.Button, {
        onClick: this.handleClickShare.bind(this),
        label: Craft.t('app', 'Share'),
        title: Craft.t('app', 'Share query'),
        key: 'shareQuery',
      }),
    ];

    let toolBar = elem(GraphiQL.Toolbar, {}, toolbarElements);

    // Render explorer and GraphiQL components side-to-side.
    return elem(
      'div',
      {className: 'graphiql-container'},
      elem(GraphiQLExplorer, {
        schema: this.state.schema,
        query: this.state.query,
        onEdit: this.handleEditQuery.bind(this),
        explorerIsOpen: this.state.explorerIsOpen,
        onToggleExplorer: this.handleClickExplorerButton.bind(this),
        ref: this.explorer,
      }),
      elem(
        GraphiQL,
        {
          fetcher: this.props.fetcher,
          schema: this.state.schema,
          query: this.state.query,
          variables: parameters.variables,
          operationName: parameters.operationName,
          onEditQuery: this.handleEditQuery.bind(this),
          onEditVariables: onEditVariables,
          ref: this.graphiql,
        },
        logoElement,
        toolBar
      )
    );
  }
}

// Export the init function
export function init(fetcher, schemas, selectedSchema) {
  return elem(CraftGraphiQL, {
    fetcher: fetcher,
    gqlSchemas: schemas,
    selectedSchema: selectedSchema,
  });
}
