import { Component, OnInit } from "@angular/core";
import { NgxSpinnerService } from "ngx-spinner";
import { ApiService } from "src/app/api.service";
import { NavbarService } from "src/app/navbar/navbar.service";
import { NotificationCenterService } from "src/app/notification-center/notification-center.service";
import { RoleService } from "src/app/rbac/role.service";

const SPINNER_NAME = "installer";

@Component({
  selector: "app-installer",
  templateUrl: "./installer.component.html",
  styleUrls: ["./installer.component.css"],
})
export class InstallerComponent implements OnInit {
  public installing: boolean = false;
  public installed: boolean = false;
  public errored: boolean = false;

  constructor(
    protected api: ApiService,
    protected notifySvc: NotificationCenterService,
    protected navbarService: NavbarService,
    protected roleService: RoleService,
    public spinner: NgxSpinnerService
  ) {}

  ngOnInit(): void {
    stateInit(this);
  }

  public reinstall(defaultPopulation = true, ignoreInvariantRules = false) {
    stateInstalling(this);
    this.notifySvc.clearNotifications();

    this.api
      .get("admin/installer", {
        params: {
          defaultPop: defaultPopulation,
          ignoreInvariantRules: ignoreInvariantRules,
        },
      })
      .then(
        (data) => {
          stateInstalled(this);

          this.roleService.deactivateAllRoles();
          this.navbarService.refreshNavBar();

          this.notifySvc.notify("Application reinstalled");
        },
        (err) => {
          stateErrored(this);
        }
      );
  }
}

function stateInit(component: InstallerComponent) {
  component.installed = false;
  component.installing = false;
  component.errored = false;
  component.spinner.hide(SPINNER_NAME);
}

function stateInstalling(component: InstallerComponent) {
  component.installed = false;
  component.installing = true;
  component.errored = false;
  component.spinner.show(SPINNER_NAME);
}

function stateInstalled(component: InstallerComponent) {
  component.installed = true;
  component.installing = false;
  component.errored = false;
  component.spinner.hide(SPINNER_NAME);
}
function stateErrored(component: InstallerComponent) {
  component.installed = false;
  component.installing = false;
  component.errored = true;
  component.spinner.hide(SPINNER_NAME);
}
