import { Injectable } from "@angular/core";
import { Patch } from "./models/patch.class";
import { Resource } from "./models/resource.class";

@Injectable({
  providedIn: "root",
})
export class ResourceService {
  constructor() {}

  public patch(): void {}
  public cancel(): void {}
  public delete(): void {}

  public saveField(resource: Resource, field: string): void {
    let value = resource[field];
    let patch = new Patch("replace", `${resource._path_}/${field}`, value);
    this.addPatch(resource, patch);
    console.log(patch);
  }

  public addPatch(resource: Resource, patch: Patch): void {
    resource._patches_.push(patch);
  }
}
