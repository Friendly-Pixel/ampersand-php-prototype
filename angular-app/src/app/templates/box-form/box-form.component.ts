import { Component, Input, OnInit } from "@angular/core";
import { CRUDComponent } from "../models/crud-component.class";
import { Resource } from "../models/resource.class";
import { ResourceService } from "../resource.service";

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
  @Input() public tgts: Array<Resource> = [];

  constructor(protected svc: ResourceService) {
    super();
  }

  ngOnInit(): void {}

  showTitle(): boolean {
    return this.title !== "";
  }

  createItem(): void {}
}
