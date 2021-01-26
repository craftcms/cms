import {init} from './CraftGraphiQL.js';
import ReactDOM from 'react-dom';

require('whatwg-fetch');

function initGraphiQl(domTarget) {
    let attributes = domTarget.attributes;
    let schemas = JSON.parse(attributes.schemas.nodeValue);
    let selectedSchema = JSON.parse(attributes.selectedSchema.nodeValue);
    let endpoint = attributes.endpoint.nodeValue;

    // Defines a GraphQL fetcher using the fetch API.
    function graphQLFetcher(graphQLParams) {
        return fetch(endpoint, {
            method: 'post',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Craft-Gql-Schema': selectedSchema.schema,
            },
            body: JSON.stringify(graphQLParams),
            credentials: 'include',
        }).then(function(response) {
            return response.text();
        }).then(function(responseBody) {
            try {
                return JSON.parse(responseBody);
            } catch (error) {
                return responseBody;
            }
        });
    }

    ReactDOM.render(init(graphQLFetcher, schemas, selectedSchema), domTarget);
}

document.addEventListener("DOMContentLoaded", function() {
    initGraphiQl(document.getElementById('graphiql'));
});
