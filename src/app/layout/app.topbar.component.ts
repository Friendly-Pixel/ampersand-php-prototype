import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { MenuItem } from 'primeng/api';
import { LayoutService } from './service/app.layout.service';
@Component({
  selector: 'app-topbar',
  templateUrl: './app.topbar.component.html',
})
export class AppTopBarComponent {
  public toolsItems!: MenuItem[];

  @ViewChild('menubutton') menuButton!: ElementRef;

  @ViewChild('topbarmenubutton') topbarMenuButton!: ElementRef;

  @ViewChild('topbarmenu') menu!: ElementRef;

  constructor(public layoutService: LayoutService) {}

  // ngOnInit(): void {
  //   this.toolsItems = [
  //     {
  //       label: 'Refresh page',
  //       icon: 'pi pi-refresh',
  //       command: () => {
  //         window.location.reload();
  //       },
  //     },
  //     {
  //       label: 'Reinstall application',
  //       icon: 'pi pi-trash',
  //       routerLink: ['/admin/installer'],
  //     },
  //     {
  //       label: '(Re)evaluate all rules)',
  //       icon: 'pi pi-check-square',
  //       command: () => {
  //         // TODO: let user know rules are evaluated
  //         this.managementAPIService.getEvaluateAllRules().subscribe();
  //       },
  //     },
  //     {
  //       label: 'Run execution engine',
  //       icon: 'pi pi-cog',
  //       command: () => {
  //         // TODO: let user know the execution engine is run
  //         this.managementAPIService.getRunExecutionEngine().subscribe();
  //       },
  //     },
  //     {
  //       label: 'Population importer',
  //       icon: 'pi pi-arrow-circle-up',
  //       routerLink: ['/ext/importer'],
  //     },
  //     {
  //       label: 'Population exporter',
  //       icon: 'pi pi-arrow-circle-down',
  //       command: () => {
  //         this.managementAPIService
  //           .getExportPopulation()
  //           .subscribe((json: Object) => this.managementAPIService.exportPopulation(json));
  //       },
  //     },
  //   ];
  // }
}
