import {d3Format} from "d3-format";
import {d3TimeFormat} from "d3-format";
// import * as d3 from "d3";

const d3 = Object.assign(
    {},
    d3Format,
    d3TimeFormat
);

export default d3;
