import { Component, Input, OnInit } from "@angular/core";
import { CRUDComponent } from "../models/crud-component.class";
import { Resource } from "../models/resource.class";
import { ResourceService } from "../resource.service";

@Component({
  selector: "app-box-form-item",
  templateUrl: "./box-form-item.component.html",
  styleUrls: ["./box-form-item.component.css"],
})
export class BoxFormItemComponent extends CRUDComponent implements OnInit {
  public status = {
    warning: false,
    danger: false,
    success: false,
  };

  public showButtons = {
    save: false,
    cancel: false,
  };

  @Input() public showNavMenu = false;
  @Input() public parent: Resource;
  @Input() public resource: Resource;

  constructor(protected svc: ResourceService) {
    super();
  }

  ngOnInit(): void {}

  save(): void {}
  cancel(): void {}
  removeItem(): void {}
  deleteItem(): void {}
}
