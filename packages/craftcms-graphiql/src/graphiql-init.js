import 'graphiql/graphiql.css';
import './graphiql.scss';
import React from 'react';
import {createRoot} from 'react-dom/client';
import GraphiQL from 'graphiql';

const CraftGraphiQL = ({endpoint, selectedSchema}) => {
  function graphQLFetcher(graphQLParams) {
    return fetch(endpoint, {
      method: 'post',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Craft-Gql-Schema': selectedSchema.schema,
      },
      body: JSON.stringify(graphQLParams),
      credentials: 'include',
    })
      .then(function (response) {
        return response.text();
      })
      .then(function (responseBody) {
        try {
          return JSON.parse(responseBody);
        } catch (error) {
          return responseBody;
        }
      });
  }

  return <GraphiQL fetcher={graphQLFetcher} forcedTheme="light" />;
};

export function init(domTarget) {
  const data = domTarget.dataset;
  const selectedSchema = JSON.parse(data.selectedSchema);
  const endpoint = data.endpoint;

  const root = createRoot(domTarget);
  root.render(
    <CraftGraphiQL endpoint={endpoint} selectedSchema={selectedSchema} />
  );
}
