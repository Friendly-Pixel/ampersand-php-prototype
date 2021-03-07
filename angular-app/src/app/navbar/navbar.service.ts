import { Injectable } from '@angular/core';
import { LocalStorageService, SessionStorageService } from 'ngx-webstorage';
import { NotificationCenterService } from '../notification-center/notification-center.service';
import { ApiService } from '../api.service';

@Injectable({
  providedIn: 'root'
})
export class NavbarService {

  constructor(
    protected api: ApiService,
    protected notificationCenter: NotificationCenterService,
    protected localStorage: LocalStorageService,
    protected sessionStorage: SessionStorageService
  ) { }

  public navbar = {
    home: null, // home/start page, can be set in project.yaml (default: '#/prototype/welcome')
    top: [],
    new: [],
    role: [],
    ext: []
  };

  public defaultSettings = {
    notify_showSignals: true,
    notify_showInfos: true,
    notify_showSuccesses: true,
    notify_autoHideSuccesses: true,
    notify_showErrors: true,
    notify_showWarnings: true,
    notify_showInvariants: true,
    autoSave: true
  };

  protected observerCallables = [];
  protected pendingNavbarPromise = null;

  protected notifyObservers() {
    this.observerCallables.forEach(callable => {
      callable();
    });
  }

  protected getNavbarPromise(): Promise<any> {
    if (this.pendingNavbarPromise === null) {
      this.pendingNavbarPromise = this.api
        .get('app/navbar')
        .finally(() => {
          this.pendingNavbarPromise = null;
        });
    }

    return this.pendingNavbarPromise;
  }

  public addObserverCallable(callable) {
    this.observerCallables.push(callable);
  }

  public getRouteForHomePage() {
    if (this.navbar.home === null) {
      return this.getNavbarPromise()
        .then((data) => {
          return data.home;
        }, error => {
          console.error('Error in getting nav bar data: ', error);
        })
    } else {
      return Promise.resolve(this.navbar.home);
    }
  }

  public refreshNavBar() {
    return this.getNavbarPromise()
      .then(data => {
        // Content of navbar
        let menus = this.treeify(data.navs, 'id', 'parent', 'children');
        this.navbar.home = data.home;

        let mainMenu = menus.find(menu => {
          return menu.id === 'MainMenu'
        });
        this.navbar.top = mainMenu === undefined ? [] : mainMenu.children;
        this.navbar.new = data.new;
        this.navbar.role = data.role;
        this.navbar.ext = data.ext;

        // Content for session storage
        this.sessionStorage.store('session', data.session);
        this.sessionStorage.store('sessionRoles', data.sessionRoles);
        this.sessionStorage.store('sessionVars', data.sessionVars);

        // Save default settings
        this.defaultSettings = data.defaultSettings;
        this.initializeSettings();

        // Update notifications
        this.notificationCenter.updateNotifications(data.notifications);

        this.notifyObservers();
      }, error => {
        this.initializeSettings();
      }).catch( error => {
        console.error(error);
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

    // set
    for (const [key, value] of Object.entries(this.defaultSettings)) {
      this.localStorage.store(key, value);
    }
  }

  /**
   * Creates a tree from flat list of elements with parent specified.
   * If no parent specified, the element is considered a root node
   * The function returns a list of root nodes
   * 'id', 'parent' and 'children' object labels can be set
   * 
   * @param {Array} list 
   * @param {string} idAttr 
   * @param {string} parentAttr 
   * @param {string} childrenAttr 
   * @returns {Array}
   */
  protected treeify(list, idAttr, parentAttr, childrenAttr) {
    if (!idAttr) idAttr = 'id';
    if (!parentAttr) parentAttr = 'parent';
    if (!childrenAttr) childrenAttr = 'children';
    var treeList = [];
    var lookup = {};
    list.forEach(obj => {
      lookup[obj[idAttr]] = obj;
      obj[childrenAttr] = [];
    });
    list.forEach(obj => {
      if (obj[parentAttr] != null) {
        if (lookup[obj[parentAttr]] === undefined) { // error when parent element is not defined in list
          console.error('Parent element is undefined: ', obj[parentAttr]);
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
