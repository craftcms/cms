const getElementIndexParams = ({perPage, page, orderBy, direction}) => {
  if (!perPage) {
    perPage = 96;
  }

  if (!page) {
    page = 1;
  }

  return {
    perPage,
    page,
    orderBy,
    direction,
  };
};

export {getElementIndexParams};
