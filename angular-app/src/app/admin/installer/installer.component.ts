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
  public numberOfCols: number;
  public installing: boolean = false;
  public installed: boolean = false;
  public errored: boolean = false;
  public buttonColor: string = "primary";

  constructor(
    protected api: ApiService,
    protected notifySvc: NotificationCenterService,
    protected navbarService: NavbarService,
    protected roleService: RoleService,
    public spinner: NgxSpinnerService
  ) {}

  ngOnInit(): void {
    this.numberOfCols = this.getColsFor(window.innerWidth);
    stateInit(this);
  }

  onResize(event) {
    this.numberOfCols = this.getColsFor(event.target.innerWidth);
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

function stateInit(component: InstallerComponent) {
  component.installed = false;
  component.installing = false;
  component.errored = false;
  component.buttonColor = "primary";
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
  component.buttonColor = "basic";
  component.spinner.hide(SPINNER_NAME);
}
function stateErrored(component: InstallerComponent) {
  component.installed = false;
  component.installing = false;
  component.errored = true;
  component.buttonColor = "warn";
  component.spinner.hide(SPINNER_NAME);
}
