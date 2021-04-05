import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BoxFormItemComponent } from './box-form-item.component';

describe('BoxFormItemComponent', () => {
  let component: BoxFormItemComponent;
  let fixture: ComponentFixture<BoxFormItemComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BoxFormItemComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(BoxFormItemComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
