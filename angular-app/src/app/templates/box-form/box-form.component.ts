import { Component, Input, OnInit } from "@angular/core";
import { BoxFormItemComponent } from "../box-form-item/box-form-item.component";

@Component({
  selector: "app-box-form",
  templateUrl: "./box-form.component.html",
  styleUrls: ["./box-form.component.css"],
})
export class BoxFormComponent implements OnInit {
  public crudC = false;
  public crudR = true;
  public crudU = false;
  public crudD = false;

  public showNavMenu = false;

  @Input()
  public title: string = "";
  public hideOnNoRecords = false;

  public isUni = true;
  public isTot = false;

  @Input()
  public tgtConceptLabel: string;

  @Input()
  public data: Array<object> = [];

  constructor() {}

  ngOnInit(): void {}

  showTitle(): boolean {
    return this.title !== "";
  }

  isCrudU(): boolean {
    return this.crudU;
  }
  isCrudD(): boolean {
    return this.crudD;
  }

  createItem(): void {}
  removeItem(item: BoxFormItemComponent): void {}
  deleteItem(item: BoxFormItemComponent): void {}
}
