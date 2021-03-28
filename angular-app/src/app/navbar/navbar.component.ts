import { Component, OnInit } from "@angular/core";
import { NavbarService } from "./navbar.service";
import { NotificationCenterService } from "../notification-center/notification-center.service";
import { LocalStorage, SessionStorage } from "ngx-webstorage";
import { Location } from "@angular/common";
import { RoleService } from "../rbac/role.service";
import { Navbar } from "./navbar";

@Component({
  selector: "app-navbar",
  templateUrl: "./navbar.component.html",
  styleUrls: ["./navbar.component.css"],
})
export class NavbarComponent implements OnInit {
  @LocalStorage() notify_showSignals: boolean;
  @LocalStorage() notify_showInvariants: boolean;
  @LocalStorage() autoSave: boolean;
  @LocalStorage() notify_showErrors: boolean;
  @LocalStorage() notify_showWarnings: boolean;
  @LocalStorage() notify_showInfos: boolean;
  @LocalStorage() notify_showSuccesses: boolean;
  @LocalStorage() notify_autoHideSuccesses: boolean;

  @SessionStorage("sessionRoles", [])
  public sessionRoles: Array<any>;
  public navbar: Navbar;

  constructor(
    protected navbarService: NavbarService,
    public notifySvc: NotificationCenterService,
    protected location: Location,
    protected roleService: RoleService
  ) {
    this.navbar = {
      top: [],
      new: [],
      role: [],
      ext: [],
    };
  }

  ngOnInit() {
    this.navbarService.refreshNavBar().then(
      (navbar) => (this.navbar = navbar),
      (err) => console.log(err)
    );
  }

  public getMenuItems(start: number, end?: number) {
    return this.navbar.top
      .sort((a, b) => a.seqNr - b.seqNr)
      .slice(start - 1, end);
  }

  public resetSettingsToDefault() {
    this.navbarService.resetSettingsToDefault();
  }

  public async toggleRole(roleId: string, set?: boolean) {
    this.roleService.toggleRole(roleId, set);
    this.roleService.setActiveRoles().then((data) => {
      this.navbarService.refreshNavBar();
    });
  }
}
