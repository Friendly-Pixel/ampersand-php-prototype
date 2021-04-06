import { Component, Input, OnInit } from "@angular/core";
import { CRUDComponent } from "../models/crud-component.class";
import { Resource } from "../models/resource.class";
import { ResourceService } from "../resource.service";

@Component({
  selector: "app-leaf-alphanumeric",
  templateUrl: "./leaf-alphanumeric.component.html",
  styleUrls: ["./leaf-alphanumeric.component.css"],
})
export class LeafAlphanumericComponent extends CRUDComponent implements OnInit {
  @Input() public crud: string;
  @Input() public isUni = true;
  @Input() public isTot = false;
  @Input() public resource: Resource;
  @Input() public field: string;

  public newValue: string;

  constructor(public resourceService: ResourceService) {
    super();
  }

  ngOnInit(): void {}

  getValues(): Array<string> {
    return Array.isArray(this.resource[this.field])
      ? (this.resource[this.field] as Array<string>)
      : ([this.resource[this.field]] as Array<string>);
  }

  // TODO: implement
  save() {
    this.resourceService.saveField(this.resource, this.field);
  }
  addItem() {
    this.newValue = "";
  }
  removeItem(index) {}
}
