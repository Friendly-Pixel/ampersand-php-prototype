import { Injectable } from "@angular/core";
import { SessionStorage } from "ngx-webstorage";
import { ApiService } from "../api.service";

@Injectable({
  providedIn: "root",
})
export class RoleService {
  @SessionStorage("sessionRoles", [])
  public sessionRoles: Array<any>;

  constructor(protected api: ApiService) {}

  public selectRole(roleId: string) {
    this.toggleRole(roleId, true);
  }

  public selectRoleByLabel(roleLabel: string) {
    this.sessionRoles.forEach((role) => {
      if (role.label == roleLabel) return this.selectRole(role.id);
    });
  }

  public toggleRole(roleId: string, set?: boolean) {
    this.sessionRoles.forEach((role) => {
      if (role.id == roleId) {
        if (set === undefined) role.active = !role.active;
        else role.active = set;
      }
    });
  }

  public getActiveRoleIds() {
    return this.sessionRoles
      .filter((role) => role.active)
      .map((role) => role.id);
  }

  public deactivateAllRoles() {
    this.sessionRoles.forEach((role) => {
      role.active = false;
    });
  }

  public async setActiveRoles() {
    return this.api.patch("app/roles", this.sessionRoles);
  }
}
