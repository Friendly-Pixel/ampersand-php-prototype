import { Component, Input, ViewChild } from "@angular/core";
import { NavItem } from "../nav-item";

@Component({
  selector: "app-menu-items",
  templateUrl: "./menu-items.component.html",
  styleUrls: ["./menu-items.component.css"],
})
export class MenuItemsComponent {
  @Input() items: NavItem[];
  @ViewChild("childMenu", { static: true }) public childMenu;

  public getChildren() {
    return this.items.sort((a, b) => a.seqNr - b.seqNr);
  }
}
