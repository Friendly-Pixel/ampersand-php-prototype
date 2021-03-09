import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InstallerComponent } from './installer.component';

describe('InstallerComponent', () => {
  let component: InstallerComponent;
  let fixture: ComponentFixture<InstallerComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InstallerComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(InstallerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
