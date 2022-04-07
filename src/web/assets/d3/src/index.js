import {d3Format} from "d3-format";
import {d3TimeFormat} from "d3-time-format";
import {d3Selection} from "d3-selection";
// import * as d3 from "d3";

const d3 = Object.assign(
    {},
    d3Format,
    d3TimeFormat,
    d3Selection,
);

export default d3;
