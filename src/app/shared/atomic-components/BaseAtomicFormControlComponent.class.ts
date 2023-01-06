import { Component, OnInit } from '@angular/core';
import { FormControl } from '@angular/forms';
import { BaseAtomicComponent } from './BaseAtomicComponent.class';

@Component({
  template: '',
})
export abstract class BaseAtomicFormControlComponent<T> extends BaseAtomicComponent<T> implements OnInit {
  public formControl!: FormControl<T>;

  override ngOnInit(): void {
    super.ngOnInit();
    this.initFormControl();
  }

  public initFormControl() {
    this.formControl = new FormControl<T>(this.data[0], { nonNullable: true, updateOn: 'blur' });
    this.formControl.valueChanges.subscribe((x) =>
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
