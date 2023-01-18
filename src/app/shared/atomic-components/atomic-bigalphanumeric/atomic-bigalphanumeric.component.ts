import { Component, OnInit } from '@angular/core';
import { FormControl } from '@angular/forms';
import { map } from 'rxjs';
import { AtomicComponentType } from '../../models/atomic-component-types';
import { BaseAtomicComponent } from '../BaseAtomicComponent.class';

@Component({
  selector: 'app-atomic-bigalphanumeric',
  templateUrl: './atomic-bigalphanumeric.component.html',
  styleUrls: ['./atomic-bigalphanumeric.component.css'],
})
export class AtomicBigalphanumericComponent extends BaseAtomicComponent<string> implements OnInit {
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

  private initFormControl(): void {
    this.formControl = new FormControl<string>(this.data[0], { nonNullable: true, updateOn: `blur` });

    if (this.canUpdate()) {
      this.formControl.valueChanges
        .pipe(map((x) => (x === '' ? null : x))) // transform empty string to null value
        .subscribe((x) =>
          this.resource
            .patch([
              {
                op: 'replace',
                path: this.propertyName, // FIXME: this must be relative to path of this.resource
                value: x,
              },
            ])
            .subscribe(),
        );
    }
  }
}
