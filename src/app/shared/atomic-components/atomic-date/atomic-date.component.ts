import { DatePipe } from '@angular/common';
import { Component, Input, OnInit } from '@angular/core';
import { FormControl } from '@angular/forms';
import { AtomicComponentType } from '../../models/atomic-component-types';
import { BaseAtomicComponent } from '../BaseAtomicComponent.class';

@Component({
  selector: 'app-atomic-date',
  templateUrl: './atomic-date.component.html',
  styleUrls: ['./atomic-date.component.css'],
})
export class AtomicDateComponent extends BaseAtomicComponent<string> implements OnInit {
  // Possible formats can be found at https://www.primefaces.org/primeng/calendar.
  // Scroll down to DateFormat for the documentation
  @Input() format: string = 'yy-mm-dd';
  public formControl!: FormControl<string>;

  override ngOnInit(): void {
    super.ngOnInit();
    if (!this.isUni) {
      this.initNewItemControl(AtomicComponentType.BigAlphanumeric);
    }
    if (this.isUni) {
      this.initFormControl();
    }
  }

  public formatDate(date: string): string {
    let datePipe: DatePipe = new DatePipe('en-US');
    return datePipe.transform(date, 'yyyy-MM-dd')!;
  }

  private initFormControl(): void {
    this.formControl = new FormControl<string>(this.data[0], { nonNullable: true, updateOn: `change` });

    if (this.canUpdate()) {
      this.formControl.valueChanges.subscribe((x) => {
        this.resource
          .patch([
            {
              op: 'replace',
              path: this.propertyName, // FIXME: this must be relative to path of this.resource
              value: this.formatDate(x),
            },
          ])
          .subscribe();
      });
    }
  }
}
