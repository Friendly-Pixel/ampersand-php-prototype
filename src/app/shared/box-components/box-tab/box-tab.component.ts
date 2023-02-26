import { Component, ContentChild, Input, TemplateRef } from '@angular/core';
import { BaseBoxComponent } from '../BaseBoxComponent.class';

@Component({
  selector: 'app-box-tab',
  templateUrl: './box-tab.component.html',
  styleUrls: ['./box-tab.component.scss'],
})
export class BoxTabComponent<TItem extends object, I> extends BaseBoxComponent<TItem, I> {
  @Input() tabHeaders: string[] = [];
  @ContentChild('tabContent') tabContent!: TemplateRef<any>;

  constructor() {
    super();
  }
}
