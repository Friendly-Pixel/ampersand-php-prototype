import { Injectable } from "@angular/core";
import { LocalStorageService, SessionStorageService } from "ngx-webstorage";
import { NotificationCenterService } from "../notification-center/notification-center.service";
import { ApiService } from "../api.service";
import * as delay from "delay";
import { defaultSettings } from "../shared/models/settings";

@Injectable({
  providedIn: "root",
})
export class NavbarService {
  public defaultSettings = defaultSettings;
  protected pendingNavbarPromise = null;

  constructor(
    protected api: ApiService,
    protected notificationCenter: NotificationCenterService,
    protected localStorage: LocalStorageService,
    protected sessionStorage: SessionStorageService
  ) {}

  protected getNavbarPromise(): Promise<any> {
    if (this.pendingNavbarPromise === null) {
      this.pendingNavbarPromise = this.api.get("app/navbar").finally(() => {
        this.pendingNavbarPromise = null;
      });
    }

    return this.pendingNavbarPromise;
  }

  public refreshNavBar() {
    return this.getNavbarPromise().then((data) => {
      // Content of navbar
      let menus = this.treeify(data.navs);

      let mainMenu = menus.find((menu) => {
        return menu.id === "MainMenu";
      });

      // Content for session storage
      this.sessionStorage.store("session", data.session);
      this.sessionStorage.store("sessionRoles", data.sessionRoles);
      this.sessionStorage.store("sessionVars", data.sessionVars);

      // Save default settings
      this.defaultSettings = data.defaultSettings;
      this.initializeSettings();

      // Update notifications
      this.notificationCenter.updateNotifications(data.notifications);

      return {
        top: mainMenu === undefined ? [] : mainMenu.children,
        new: data.new,
        role: data.role,
        ext: data.ext,
      };
    });
  }

  public initializeSettings() {
    let resetRequired = false;

    // Check for undefined settings
    for (const [key, value] of Object.entries(this.defaultSettings)) {
      if (this.localStorage.retrieve(key) === null) {
        resetRequired = true;
      }
    }

    if (resetRequired) this.resetSettingsToDefault();
  }

  public resetSettingsToDefault() {
    this.localStorage.clear();

    // wait for half a sec. to have visual effect in navbar
    setTimeout(async () => {
      for (const [key, value] of Object.entries(this.defaultSettings)) {
        // handle case 'false' differently to have visual effect
        if (value === false) {
          // first switch on
          this.localStorage.store(key, !value);

          // after 100 ms switch off
          setTimeout(() => {
            this.localStorage.store(key, value);
          }, 100);
        } else {
          this.localStorage.store(key, value);
        }
        await delay(100);
      }
    }, 500);
  }

  /**
   * Creates a tree from flat list of elements with parent specified.
   * If no parent specified, the element is considered a root node
   * The function returns a list of root nodes
   * 'id', 'parent' and 'children' object labels can be set
   */
  protected treeify(
    list: Array<any>,
    idAttr = "id",
    parentAttr = "parent",
    childrenAttr = "children"
  ): Array<any> {
    let treeList = [];
    let lookup = {};
    list.forEach((obj) => {
      lookup[obj[idAttr]] = obj;
      obj[childrenAttr] = [];
    });
    list.forEach((obj) => {
      if (obj[parentAttr] != null) {
        if (lookup[obj[parentAttr]] === undefined) {
          // error when parent element is not defined in list
          console.error("Parent element is undefined: ", obj[parentAttr]);
        } else {
          lookup[obj[parentAttr]][childrenAttr].push(obj);
          obj[parentAttr] = lookup[obj[parentAttr]]; // replace parent id with reference to actual parent element
        }
      } else {
        treeList.push(obj);
      }
    });
    return treeList;
  }
}
