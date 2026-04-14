import {currency} from '../filters/currency';

const getPriceLabel = (price) => {
  return price > 0 ? currency(price) : 'Free';
};

const getPriceRange = (editions) => {
  let min = null;
  let max = null;

  for (let i = 0; i < editions.length; i++) {
    const edition = editions[i];

    let price = 0;

    if (edition.price) {
      price = parseInt(edition.price);
    }

    if (min === null) {
      min = price;
    }

    if (max === null) {
      max = price;
    }

    if (price < min) {
      min = price;
    }

    if (price > max) {
      max = price;
    }
  }

  return {
    min,
    max,
  };
};

const getPriceRangeLabel = (plugin) => {
  const {min, max} = getPriceRange(plugin.editions);

  if (min !== max) {
    return `${getPriceLabel(min)} – ${getPriceLabel(max)}`;
  }

  return getPriceLabel(min);
};

const isPluginFree = (plugin) => {
  const {min, max} = getPriceRange(plugin.editions);

  if (min !== 0 || max !== 0) {
    return false;
  }

  return true;
};

export {getPriceLabel, getPriceRange, getPriceRangeLabel, isPluginFree};
