import { Component, Input, OnInit } from "@angular/core";
import { BoxFormItemComponent } from "../box-form-item/box-form-item.component";
import { CRUDComponent } from "../crud-component.class";

@Component({
  selector: "app-box-form",
  templateUrl: "./box-form.component.html",
  styleUrls: ["./box-form.component.css"],
})
export class BoxFormComponent extends CRUDComponent implements OnInit {
  @Input() public crud: string;
  @Input() public title: string = "";
  @Input() public hideOnNoRecords = false;
  @Input() public showNavMenu = false;
  @Input() public isUni = true;
  @Input() public isTot = false;
  @Input() public tgtConceptLabel: string;
  @Input() public data: Array<object> = [];

  constructor() {
    super();
  }

  ngOnInit(): void {}

  showTitle(): boolean {
    return this.title !== "";
  }

  createItem(): void {}
  removeItem(item: BoxFormItemComponent): void {}
  deleteItem(item: BoxFormItemComponent): void {}
}
