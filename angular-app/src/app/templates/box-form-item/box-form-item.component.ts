import { Component, Input, OnInit } from "@angular/core";
import { BoxFormComponent } from "../box-form/box-form.component";
import { CRUDComponent } from "../crud-component.class";

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

  @Input()
  public showNavMenu = false;

  @Input()
  public parent: BoxFormComponent;

  @Input()
  public data: object;

  constructor() {
    super();
  }

  ngOnInit(): void {}

  save(): void {}
  cancel(): void {}
  removeItem(): void {
    this.parent.removeItem(this);
  }
  deleteItem(): void {
    this.parent.deleteItem(this);
  }
}
