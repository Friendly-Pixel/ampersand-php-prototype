import { Component, OnInit } from "@angular/core";
import { NavbarService } from "./navbar.service";
import { NotificationCenterService } from "../notification-center/notification-center.service";
import { LocalStorage, SessionStorage } from "ngx-webstorage";
import { Location } from "@angular/common";
import { RoleService } from "../rbac/role.service";

interface NavBar {
  home: string;
  top: Array<any>;
  new: Array<any>;
}

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

  public navbar: NavBar;
  public resetSettingsToDefault;
  public checkAllRules;

  constructor(
    protected navbarService: NavbarService,
    protected notificationService: NotificationCenterService,
    protected location: Location,
    protected roleService: RoleService
  ) {}

  ngOnInit() {
    this.navbarService.refreshNavBar();
    this.navbar = this.navbarService.navbar;
    this.resetSettingsToDefault = this.navbarService.resetSettingsToDefault;
    this.checkAllRules = this.notificationService.checkAllRules;
  }

  public getMenuItems(start: number, end?: number) {
    return this.navbar.top
      .sort((a, b) => a.seqNr - b.seqNr)
      .slice(start - 1, end);
  }

  public async toggleRole(roleId: string, set?: boolean) {
    this.roleService.toggleRole(roleId, set);
    this.roleService.setActiveRoles().then((data) => {
      this.navbarService.refreshNavBar();
    });
  }
}
