import { Patch } from "./patch.class";

export class Resource {
  _id_: string;
  _path_: string;
  _patches_: Array<Patch>;
  [key: string]:
    | Resource
    | Array<Resource>
    | Scalar
    | Array<Scalar>
    | null
    | Array<Patch>; // this one is here because property _patches_ must also match

  constructor(input: ResourceJson) {
    this._id_ = input._id_;
    this._path_ = input._path_;
    this._patches_ = [];
    for (const [key, value] of Object.entries(input)) {
      this[key] = castValue(value);
    }
  }
}

interface ResourceJson {
  _id_: string;
  _path_: string;
  [key: string]: any;
}

type Scalar = boolean | string | number;

function castValue(value: any): any {
  // Note 'object's are: {}, Array and null
  if (typeof value === "object") {
    if (Array.isArray(value)) {
      return value.map((item) => castValue(item));
    } else if (value === null) {
      return value;
    } else {
      return new Resource(value);
    }
  } else {
    return value;
  }
}
