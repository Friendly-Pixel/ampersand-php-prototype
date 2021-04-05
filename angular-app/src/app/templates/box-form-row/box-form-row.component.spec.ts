import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BoxFormRowComponent } from './box-form-row.component';

describe('BoxFormRowComponent', () => {
  let component: BoxFormRowComponent;
  let fixture: ComponentFixture<BoxFormRowComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BoxFormRowComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(BoxFormRowComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
