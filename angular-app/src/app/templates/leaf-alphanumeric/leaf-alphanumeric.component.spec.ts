import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LeafAlphanumericComponent } from './leaf-alphanumeric.component';

describe('LeafAlphanumericComponent', () => {
  let component: LeafAlphanumericComponent;
  let fixture: ComponentFixture<LeafAlphanumericComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LeafAlphanumericComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LeafAlphanumericComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
