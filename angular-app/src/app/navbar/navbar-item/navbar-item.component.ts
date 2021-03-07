import { Component, Input } from "@angular/core";
import { NavItem } from "../nav-item";

@Component({
  selector: "app-navbar-item",
  templateUrl: "./navbar-item.component.html",
  styleUrls: ["./navbar-item.component.css"],
})
export class NavbarItemComponent {
  @Input()
  public item: NavItem;
}
