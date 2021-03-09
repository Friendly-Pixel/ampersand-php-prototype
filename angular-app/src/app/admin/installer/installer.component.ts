import { Component, OnInit } from "@angular/core";
import { ApiService } from "src/app/api.service";
import { NavbarService } from "src/app/navbar/navbar.service";
import { NotificationCenterService } from "src/app/notification-center/notification-center.service";
import { RoleService } from "src/app/rbac/role.service";

@Component({
  selector: "app-installer",
  templateUrl: "./installer.component.html",
  styleUrls: ["./installer.component.css"],
})
export class InstallerComponent implements OnInit {
  public numberOfCols: number;
  public installing: boolean = false;
  public installed: boolean = false;

  constructor(
    protected api: ApiService,
    protected notificationCenter: NotificationCenterService,
    protected navbarService: NavbarService,
    protected roleService: RoleService
  ) {}

  ngOnInit(): void {
    this.numberOfCols = this.getColsFor(window.innerWidth);
  }

  onResize(event) {
    this.numberOfCols = this.getColsFor(event.target.innerWidth);
  }

  public reinstall(defaultPopulation = true, ignoreInvariantRules = false) {
    this.installing = true;
    this.installed = false;
    this.notificationCenter.clearNotifications();

    this.api
      .get("admin/installer", {
        params: {
          defaultPop: defaultPopulation,
          ignoreInvariantRules: ignoreInvariantRules,
        },
      })
      .then(
        (data) => {
          this.notificationCenter.updateNotifications(data);
          this.navbarService.refreshNavBar();

          // deactive all roles
          this.roleService.deactivateAllRoles();

          this.installing = false;
          this.installed = true;
        },
        (err) => {
          this.installing = false;
          this.installed = false;
        }
      );
  }

  protected getColsFor(width: number): number {
    if (width < 600) {
      return 1;
    }
    if (width < 1024) {
      return 2;
    }
    if (width < 1440) {
      return 3;
    }

    return 4;
  }
}
