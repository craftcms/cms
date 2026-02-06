/**
 * Requests a URL and downloads the response.
 *
 * @param {string} method the request method to use
 * @param {string} url the URL
 * @param {(string|Object)} [body] the request body, if method = POST
 * @returns {Promise}
 */
export function downloadFromUrl(
  method: string,
  url: string,
  body: string | Record<any, any>
): Promise<void> {
  return new Promise((resolve, reject) => {
    // h/t https://nehalist.io/downloading-files-from-post-requests/
    let request = new XMLHttpRequest();
    request.open(method, url, true);
    if (typeof body === 'object') {
      request.setRequestHeader(
        'Content-Type',
        'application/json; charset=UTF-8'
      );
      body = JSON.stringify(body);
    } else {
      request.setRequestHeader(
        'Content-Type',
        'application/x-www-form-urlencoded; charset=UTF-8'
      );
    }
    request.responseType = 'blob';

    request.onload = () => {
      // Only handle status code 200
      if (request.status === 200) {
        // Try to find out the filename from the content disposition `filename` value
        let disposition = request.getResponseHeader('content-disposition');
        let matches = disposition ? /"([^"]*)"/.exec(disposition) : null;
        let filename = matches != null && matches[1] ? matches[1] : 'Download';

        // Encode the download into an anchor href
        let contentType = request.getResponseHeader('content-type') ?? '';
        let blob = new Blob([request.response], {type: contentType});
        let link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        resolve();
      } else {
        reject();
      }
    };

    request.send(body);
  });
}
