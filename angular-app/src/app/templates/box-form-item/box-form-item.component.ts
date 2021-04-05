import { Component, Input, OnInit } from "@angular/core";
import { BoxFormComponent } from "../box-form/box-form.component";

@Component({
  selector: "app-box-form-item",
  templateUrl: "./box-form-item.component.html",
  styleUrls: ["./box-form-item.component.css"],
})
export class BoxFormItemComponent implements OnInit {
  public crudC = false;
  public crudR = true;
  public crudU = false;
  public crudD = false;

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

  constructor() {}

  ngOnInit(): void {}

  isCrudU(): boolean {
    return this.crudU;
  }
  isCrudD(): boolean {
    return this.crudD;
  }

  save(): void {}
  cancel(): void {}
  removeItem(): void {
    this.parent.removeItem(this);
  }
  deleteItem(): void {
    this.parent.deleteItem(this);
  }
}
