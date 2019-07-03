import { Injectable } from '@angular/core';
import { Restangular } from 'ngx-restangular';

@Injectable({
  providedIn: 'root'
})
export class NavbarService {

  constructor(private restangular: Restangular) { }

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

  protected getNavbarPromise() {
    if (this.pendingNavbarPromise === null) {
      this.pendingNavbarPromise = this.restangular
        .one('app/navbar')
        .get()
        .finally(function () {
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
        .then(function (data) {
          return data.home;
        }, function (error) {
          console.error('Error in getting nav bar data: ', error);
        })
    } else {
      return Promise.resolve(this.navbar.home);
    }
  }

  public refreshNavBar() {
    return this.getNavbarPromise()
      .then(function (data) {
        // Content of navbar
        let hasChildren = function () {
          return this.children.length > 0;
        };
        let navItems = data.navs.map(function (item) {
          item.hasChildren = hasChildren.bind(item);
          return item;
        });
        let menus = this.treeify(navItems, 'id', 'parent', 'children');
        this.navbar.home = data.home;

        let mainMenu = menus.find(function (menu) {
          return menu.id === 'MainMenu'
        });
        this.navbar.top = mainMenu === undefined ? [] : mainMenu.children;
        this.navbar.new = data.new;
        this.navbar.role = data.role;
        this.navbar.ext = data.ext;

        // Content for session storage
        $sessionStorage.session = data.session;
        $sessionStorage.sessionRoles = data.sessionRoles;
        $sessionStorage.sessionVars = data.sessionVars;

        // Save default settings
        this.defaultSettings = data.defaultSettings;
        this.initializeSettings();

        // Update notifications
        NotificationService.updateNotifications(data.notifications);

        this.notifyObservers();
      }, function (error) {
        this.initializeSettings();
      }).catch(function (error) {
        console.error(error);
      });
  }

  public initializeSettings() {
    let resetRequired = false;

    // Check for undefined settings
    this.defaultSettings.forEach(function (value, index, obj) {
      if ($localStorage[index] === undefined) {
        resetRequired = true;
      }
    });

    if (resetRequired) this.resetSettingsToDefault();
  }

  public resetSettingsToDefault() {
    // all off
    this.defaultSettings.forEach(function (value, index, obj) {
      $localStorage[index] = false;
    });

    $timeout(function () {
      // Reset to default
      $localStorage.$reset(this.defaultSettings);
    }, 500);
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
    list.forEach(function (obj) {
      lookup[obj[idAttr]] = obj;
      obj[childrenAttr] = [];
    });
    list.forEach(function (obj) {
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
